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

(function ($) {

    var KlarnaProceedAction = {
        options: {
            sAction: 'actionKlarnaExpressCheckoutFromDetailsPage',
            sForm: '#detailsMain form.js-oxProductForm'
        },

        _create: function () {
            var self = this;

            $(self.element).click(function () {
                if (!$(self.element).hasClass('disabled')) {
                    $(self.options.sForm + ' input[name="fnc"]').val(self.options.sAction);
                    $(self.options.sForm).submit();
                }
            });
        }
    };

    $.widget("ui.KlarnaProceedAction", KlarnaProceedAction);
})(jQuery);

/**
 * Moves template contents to loginbox (after submit button)
 * Because there are no blocks in loginbox template to override
 *
 * @param lawNoticeTemplate
 */
function moveLawNotice(lawNoticeTemplate){
    var submitButton = document.querySelector("#loginBox button[type='submit']");

    if (lawNoticeTemplate && submitButton) {
        submitButton.insertAdjacentHTML('beforebegin', lawNoticeTemplate);
    }
}

