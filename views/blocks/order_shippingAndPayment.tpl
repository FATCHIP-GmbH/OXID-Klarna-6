[{$smarty.block.parent}]

[{if $payment && $payment->isKPPayment()}]
    [{capture assign="insertLogoJS" }]
        var $parent = $('#orderPayment').find('.panel-body');
        var parentStyle = getComputedStyle($parent[0]);
        var offset = 5;
        var height = parseInt(parentStyle.height) - offset * 2;
        var margin = parseInt(parentStyle.paddingTop) - offset;
        $('<img>')
            .attr('src', "[{$payment->getBadgeUrl()}]")
            .attr('height', height + 'px')
            .css({'margin': '-' + margin + 'px 10px'})
            .appendTo($parent)
    [{/capture}]
    [{ oxscript add=$insertLogoJS priority=10 }]
[{/if}]
