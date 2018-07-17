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

if (!Array.prototype.remove) {
    Array.prototype.remove = function (item) {
        var index = this.indexOf(item);
        if (index !== -1)
            this.splice(index, 1);

        return this;
    };
}

var KlarnaApi;

(function () {
    function getCookie(cname) {
        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) === 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }


    $('.drop-trigger').each(function () {
        $(this).click(function () {
            var clicked = this;
            $(clicked)
                .parent().toggleClass('active')
                .find('.drop-content')
                .toggle(400)
            ;

            $('.drop-container').each(function () {
                if (clicked !== $(this).find('.drop-trigger')[0]) {
                    $(this).removeClass('active')
                        .find('.drop-content')
                        .hide(400)
                    ;
                }
            });
        });
    });

    var widgetPrototype = {

        sendRequest: function (requestObject, callback) {

            KlarnaApi.suspend();
            requestObject.done(
                (function (response) {
                    callback.call(this, response);
                    KlarnaApi.resume();
                }).bind(this)
            );
        },

        updateErrors: function (html) {

            $('#content .alert-danger').remove();
            var $errors = $(html);
            $errors.css({display: 'none'})
                .prependTo('#content')
                .slideDown(function () {
                    setTimeout(function () {
                        $errors.slideUp();
                    }, 4000);
                });
        }
    };

    var addressWidget = $.extend(Object.create(widgetPrototype),
        {
            $form: $('form[name=address]'),
            $items: $('.js-klarna-address-list-item'),
            $selected: $('.js-klarna-selected-address'),
            $input: $('input[name="klarna_address_id"]'),
            $submitButton: $('#setDeliveryAddress'),

            selectAddress: function (event) {

                this.$selected.text(event.target.innerHTML);
                this.$input.val(
                    event.target
                        .parentNode
                        .getAttribute('data-address-id')
                );
            },

            // submitAddress: function (event) {
            //
            //     event.preventDefault();
            //     var formData = this.$form.serializeArray();
            //     this.sendRequest($.post(this.$form.attr('action'), formData),
            //         function (response) {
            //             return;
            //         }
            //     );
            //
            //     $(event.target).closest('.drop-container').removeClass('active')
            //         .find('.drop-content').hide(400);
            // },

            onInit: function () {

                this.$items.click(this.selectAddress.bind(this));
                // this.$submitButton.click(this.submitAddress.bind(this));
            }
        }
    );

    var vouchersWidget = $.extend(Object.create(widgetPrototype),
        {
            $content: null,
            $form: $('form[name=voucher]'),
            $input: $('input[name=voucherNr]'),
            $submitButton: $('#submitVoucher'),

            submitVoucher: function (event) {
                event.preventDefault();
                var formData = this.$form.serializeArray();
                this.sendRequest(
                    $.post(this.$form.attr('action'), formData),
                    this.updateWidget
                );
            },

            removeVoucher: function (event) {

                event.preventDefault();
                var url = event.target.href ? event.target.href : event.target.closest('a').href;
                this.sendRequest($.get(url), this.updateWidget);
            },

            /**
             * Updates widget content and handling error displaying
             * If additional
             * @param response json response
             */
            updateWidget: function (response) {
                var data = JSON.parse(response);
                this.updateErrors(data.error);
                this.$content.find('.voucherData').html(data.vouchers);

            },

            onInit: function () {

                this.$content = this.$form.closest('.drop-content');
                this.$submitButton.click(this.submitVoucher.bind(this));
                this.$content.on('click', '.couponData a', this.removeVoucher.bind(this));
            }
        }
    );

    // initialize widgets
    addressWidget.onInit();
    vouchersWidget.onInit();

    window._klarnaCheckout(function (api) {
        KlarnaApi = api;

        /** vars track changes of this values during the 'change' event */
        var country, eventsInProgress = [];

        var klarnaSendXHR = function (data, suspendMode) {

            if(eventsInProgress.indexOf(data.action) > -1){
                console.warn('ACTION ' + data.action + ' already in progress.');
                return;
            }
            eventsInProgress = eventsInProgress.concat(data.action);

            suspendMode = typeof suspendMode !== 'undefined' ? suspendMode : true;
            if (suspendMode)
                api.suspend();

            return $.ajax({
                type: 'POST',
                dataType: 'json',
                url: '?cl=order&fnc=updateKlarnaAjax',
                data: JSON.stringify(data),
                statusCode: {
                    200: function () {
                        eventsInProgress.remove(data.action);
                        if (suspendMode){
                            api.resume();
                        }
                    }
                }
            }).success(function (response) {
                if (response.status === 'redirect') {
                    localStorage.setItem('skipKlarnaEvents', '1');  // will skip ajax events on iframe render
                    window.location.href = response.data.url;
                }

                if(response.status === 'update_voucher_widget'){
                    $.get('?cl=KlarnaAjax&fnc=updateVouchers', vouchersWidget.updateWidget.bind(vouchersWidget));
                }
            });
        };

        api.on({
            'shipping_option_change': function shipping_option_change(eventData) {
                // console.log("Event:" + arguments.callee.name, eventData);
                eventData.action = arguments.callee.name;
                klarnaSendXHR(eventData);
            },

            'shipping_address_change': function shipping_address_change(eventData) {
                // console.log("Event:" + arguments.callee.name, eventData);
                eventData.action = arguments.callee.name;
                klarnaSendXHR(eventData);
            },

            'change': function change(eventData) {
                eventData.action = arguments.callee.name;
                // Shows modal after iframe is loaded and there is no user data injected
                if (getCookie('blockCountryModal') !== '1') {
                    if (showModal) {
                        $('#myModal').modal('show');
                        document.cookie = "blockCountryModal=1"
                    }
                }

                // Sends newly selected country to the backend
                if (country && (country !== eventData.country)) {
                    klarnaSendXHR(eventData, false);
                }
                country = eventData.country;

            }
        });
    });
})();