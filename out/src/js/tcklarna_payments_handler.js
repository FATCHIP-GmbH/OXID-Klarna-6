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


    if ($form.attr('id') === 'orderConfirmAgbBottom') {
        $form.find('input[name=fnc]').val('kpBeforeExecute');
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
                        var $outter = $('#' + id).closest('.kp-outer')
                        var $radio = $outter.find('input.kp-radio');

                        $outter.hide();
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
        } else {
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
            setTimeout(function () {
                $('.loading').hide(1000);
            }, 3000);
            Klarna.Payments.finalize(options, {}, authorizationHandler);
        } catch (e) {
            console.error(e)
        }
    }

    function authorizationHandler(response) {
        console.log(response);
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
                if($form.attr('id') === 'orderConfirmAgbBottom'){
                    finalize({data: recentResponse});
                    return;
                }
            }
            $sbmButton.attr('disabled', true);
            $('<input>').attr({
                type: 'hidden',
                name: 'sAuthToken',
                value: response.authorization_token
            }).appendTo($form);

            $form.submit();


        } else if (response.show_form === false) {
            $($kpRadio.active).closest('.kp-outer').hide(600);
        }
    }


    function klarnaSendXHR(data) {
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '?cl=order&fnc=updateKlarnaAjax',
            data: JSON.stringify(data),
            statusCode: {
                404: function () {
                    klarnaSendXHR(data);
                },
                200: handleResponse
            }
        })

    }

    function handleResponse(objResponse) {
        console.log(objResponse);
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
                } else {
                    $form.submit();
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

        if(clientToken){
            try {
                Klarna.Payments.init({
                    client_token: clientToken
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

                if (!this.hasError && clientToken) {
                    loadWidget(this.paymentMethod);
                }
                if (this.checked) {
                    this.$klarnaDiv.show(600);
                    $kpRadio.active = this;
                }
            });
        }

        // click
        if ($form.attr('id') === 'payment') {
            $kpRadio.click(function () {

                if (!this.hasError && clientToken) {
                    // now we can update user data
                    klarnaSendXHR({
                        action: 'addUserData',
                        paymentId: this.value,
                        client_token: clientToken
                    });
                }

                // show/hide KP methods on payment change
                $(this)
                    .closest('.kp-outer')
                    .find('.kp-method')
                    .show(600);

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

            // order step4 click
            $('#orderStep').attr('href', 'javascript:document.getElementById("paymentNextStepBottom").click();');
        }

        // Override form submission
        $sbmButton.click(function (event) {
            event.preventDefault();
            if ($kpRadio.active && $form.attr('id') === 'payment') {
                if (!$kpRadio.active.hasError) {
                    $('.loading').show(600);
                    klarnaSendXHR({
                        action: 'checkOrderStatus',
                        paymentId: $kpRadio.active.value,
                        client_token: clientToken
                    });
                }

            } else if ($form.attr('id') === 'orderConfirmAgbBottom') {
                $('.loading').show(600);
                var downloadItemAgreement = $('input[type=checkbox][name="oxdownloadableproductsagreement"]').get(0);
                if(downloadItemAgreement && downloadItemAgreement.checked === false){
                    $form.submit();
                    return;
                }

                klarnaSendXHR({
                    action: 'checkOrderStatus',
                    client_token: clientToken
                });

            } else {
                $form.submit();
            }
        });

        Klarna.Payments.on('fullscreenOverlayHidden', function(){
            $('.loading').hide(600);
            console.log('Spinner is hidden.');
        });

    })();
};

