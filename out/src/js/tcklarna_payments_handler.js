/**
 * Copyright 2018 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

window.klarnaAsyncCallback = function () {
    var $form = $('form#payment, form#orderConfirmAgbBottom');
    var $sbmButton = $form.find('[type=submit]');
    var $kpRadio = $('input[type=radio].kp-radio');
    var $otherRadio = $('input[type=radio]:not(.kp-radio)');
    var recentResponse;

    function getParameterByName(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    function replaceContentWithMessage($container){
        $container.find('input.kp-radio').get(0).hasError = true;
        $container.find('.kp-method').replaceWith(
            $('.tcklarna-message:first').clone()
        );
    }

    function loadWidget(id) {
        $('.loading').show(400);
        $kpRadio.counter++;
        try {
            Klarna.Payments.load(
                {
                    container: '#' + id,
                    payment_method_category: id
                },
                function (response) {
                    if (response.show_form === false) {
                        var $outter = $('#' + id).closest('.kp-outer');
                        var $radio = $outter.find('input.kp-radio');

                        if (tcKlarnaIsB2B && id !== 'pay_later'){
                            replaceContentWithMessage($outter);
                        } else {
                            $outter.hide();
                        }
                        $radio.get(0).checked = false;
                        if ($kpRadio.active === $radio.get(0)) {
                            $kpRadio.active.checked = false;
                            delete $kpRadio.active;
                        }
                    }

                    $kpRadio.counter--;
                    if ($kpRadio.counter === 0)
                        $('.loading').hide(1000);
                }
            );
        } catch(e){
            console.debug(e);
        }
    }

    function authorize() {
        if (typeof $kpRadio.active !== 'undefined') {
            try {
                var options = {
                    payment_method_category: $($kpRadio.active).data('payment_id'),
                    auto_finalize: false
                };
                Klarna.Payments.authorize(options, {}, authorizationHandler);
            } catch (e) {
                return console.error(e)
            }
        } else if (window.keborderpayload) {
            try {
                var options = {
                    payment_method_category: 'klarna',
                    auto_finalize: false
                };
                Klarna.Payments.authorize(options, {}, authorizationHandler);
            } catch (e) {
                return console.error(e)
            }
        }
        else {
            //todo: redirect to payment .. ?
            console.error('Klarna Payment method must be selected.');
        }
    }

    function reauthorize(data) {
        recentResponse = data;
        try {
            var $kpRadioChecked = $('.kp-radio:checked');
            var options = {payment_method_category: data.paymentMethod};
            if ($kpRadioChecked.length > 0)
                paymentMethod = $kpRadioChecked.data('payment_id');

            Klarna.Payments.reauthorize(options, {}, authorizationHandler);
        } catch (e) {
            console.error(e)
        }
    }

    function finalize(objResponse) {
        try {
            var options = {
                payment_method_category: objResponse.data.paymentMethod
            };

            if (window.keborderpayload) {
                options = {payment_method_category: 'klarna'}
            }
            setTimeout(function () {
                $('.loading').hide(1000);
            }, 3000);
            Klarna.Payments.finalize(options, {}, authorizationHandler);
        } catch (e) {
            console.error(e)
        }
    }

    function authorizationHandler(response) {
        if (response.approved === true) {

            // pay now method
            if (response.finalize_required === true) {
                if($form.attr('id') === 'payment'){
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'finalizeRequired',
                        value: true
                    }).appendTo($form);
                }
                if($form.attr('id') === 'orderConfirmAgbBottom' && window.keborderpayload){
                    finalize({data: window.keborderpayload});
                    return;
                }
                if($form.attr('id') === 'orderConfirmAgbBottom'){
                    finalize({data: recentResponse});
                    return;
                }
            }

            $('<input>').attr({
                type: 'hidden',
                name: 'sAuthToken',
                value: response.authorization_token
            }).appendTo($form);

            if($form.attr('id') === 'orderConfirmAgbBottom' && window.keborderpayload){
                $('<input>').attr({
                                      type: 'hidden',
                                      name: 'kexpaymentid',
                                      value: 'klarna_pay_now'
                                  }).appendTo($form);
                $('input[name="stoken"]').val(kebordertoken);
                $('input[name="sDeliveryAddressMD5"]').val(kebordermd5);
            }

            $sbmButton.attr('disabled', true);
            $form.submit();

        } else if (response.show_form === false) {
            $($kpRadio.active).closest('.kp-outer').hide(600);
        }
    }


    function klarnaSendXHR(data) {
        var urlShopId = getParameterByName('shp', window.location.search);
        var urlShopParam = urlShopId ? '&shp=' + urlShopId : '';

        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '?cl=order&fnc=updateKlarnaAjax' + urlShopParam,
            data: JSON.stringify(data),
            statusCode: {
                200: handleResponse
            }
        })

    }

    function handleResponse(objResponse) {
        switch (objResponse.status) {

            case 'reauthorize':
                reauthorize(objResponse.data);
                break;

            case 'authorize':
                authorize();
                break;

            case 'redirect':
                window.location.href = objResponse.data.url;
                break;

            case 'finalize':
                if($form.attr('id') === 'orderConfirmAgbBottom') {
                    finalize(objResponse);
                }
                break;

            case 'refresh':
                window.location.href = objResponse.data.refreshUrl;
                break;

            case 'submit':
                $form.submit();
                break;
        }
    }

    (function initKlarnaPayment() {

        if(tcKlarnaClientToken){
            try {
                Klarna.Payments.init({
                    client_token: tcKlarnaClientToken
                });

            } catch (e) {
                console.error(e);
            }
        }

        if ($form.attr('id') === 'payment') {
            $kpRadio.counter = 0;
            $kpRadio.each(function () {
                this.paymentMethod = $(this).data('payment_id');
                this.$klarnaDiv = $(this).closest('dl').find('.kp-method');
                this.hasError = this.$klarnaDiv.hasClass('alert');

                if (!this.hasError && tcKlarnaClientToken) {
                    loadWidget(this.paymentMethod);
                }
                if (this.checked && !tcKlarnaIsB2B) {
                    this.$klarnaDiv.show(600);
                    $kpRadio.active = this;
                }else if (this.checked) {
                    $kpRadio.active = this;
                }
            });
        }

        // click
        if ($form.attr('id') === 'payment') {
            $kpRadio.click(function () {
                if (!this.hasError && tcKlarnaClientToken) {
                    // now we can update user data
                    klarnaSendXHR({
                        action: 'addUserData',
                        paymentId: this.value,
                        client_token: tcKlarnaClientToken
                    });
                }

                // show/hide KP methods on payment change
                if (!tcKlarnaIsB2B) {
                    $(this)
                        .closest('.kp-outer')
                        .find('.kp-method')
                        .show(600);
                }

                $kpRadio.each((function (i, node) {
                    if (node !== this) {
                        $(node)
                            .closest('.kp-outer')
                            .find('.kp-method')
                            .hide(600);
                    }
                }).bind(this));

                $kpRadio.active = this;

            });

            // hide KP methods if other payment selected
            $otherRadio.click(function () {
                delete $kpRadio.active;
                $('.kp-method').hide(600);
            });
            
            // react to aggrosoft paypal plus changes
            $('body').on('ppplus-payment-selected', function() {
               delete $kpRadio.active;
               $('.kp-method').hide(600);
            });

            // order step4 click
            $('#orderStep').attr('href', 'javascript:document.getElementById("paymentNextStepBottom").click();');
        }

        // Override form submission
        $sbmButton.click(function (event) {
            if (window.keborderpayload) {
                event.preventDefault();
                $('<input>').attr({
                      type: 'hidden',
                      name: 'kexpaymentid',
                      value: 'klarna_pay_now'
                  }).appendTo($form);
                handleResponse({status: "finalize",data: window.keborderpayload})
            } else if ($kpRadio.active && $form.attr('id') === 'payment') {
                event.preventDefault();
                if (!$kpRadio.active.hasError) {
                    $('.loading').show(600);
                    klarnaSendXHR({
                        action: 'checkOrderStatus',
                        paymentId: $kpRadio.active.value,
                        client_token: tcKlarnaClientToken
                    });
                }
            } else if ($form.attr('id') === 'orderConfirmAgbBottom') {
                event.preventDefault();
                $('.loading').show(600);
                var downloadItemAgreement = $('input[type=checkbox][name="oxdownloadableproductsagreement"]').get(0);
                if(downloadItemAgreement && downloadItemAgreement.checked === false){
                    $form.submit();
                    return;
                }

                klarnaSendXHR({
                    action: 'checkOrderStatus',
                    client_token: tcKlarnaClientToken
                });

            }
        });

        Klarna.Payments.on('fullscreenOverlayHidden', function(){
            $('.loading').hide(600);
            console.log('Spinner is hidden.');
        });
    })();
};

