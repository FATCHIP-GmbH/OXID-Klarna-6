(function () {

    $('.row-label').click(function () {
        var $parent = $(this).parent();
        $parent.find('.rows-wrapper:first').toggle(400);
        $parent.find('.sign').toggleClass('minus');
        $parent.toggleClass('bg-light');
    });

    $('.js-payment-history-toggle').click(function () {
        var $parent = $(this).closest('table').find('.js-payment-history-options');
        $parent.toggle(400);
    });

})();