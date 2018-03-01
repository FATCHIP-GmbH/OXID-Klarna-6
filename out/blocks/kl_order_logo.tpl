[{$smarty.block.parent}]

[{if $payment && $payment->isKPPayment()}]
    [{assign var="klarnaBadgeName" value=$payment->getKlarnaBadgeName()}]
    [{capture assign="insertLogoJS" }]
        var $parent = $('#orderPayment').find('.panel-body');
        var parentStyle = getComputedStyle($parent[0]);
        var offset = 5;
        var height = parseInt(parentStyle.height) - offset * 2;
        var margin = parseInt(parentStyle.paddingTop) - offset;
        $('<img>')
            .attr('src', "[{"//cdn.klarna.com/1.0/shared/image/generic/badge/%s/%s/standard/pink.svg"|sprintf:$sLocale:$klarnaBadgeName}]")
            .attr('height', height + 'px')
            .css({'margin': '-' + margin + 'px 10px'})
            .appendTo($parent)
    [{/capture}]
    [{ oxscript add=$insertLogoJS priority=10 }]
[{/if}]
