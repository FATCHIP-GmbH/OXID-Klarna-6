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

