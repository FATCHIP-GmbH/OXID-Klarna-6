[{if $sPaymentId !== 'klarna_checkout' && $sPaymentId !== 'klarna_instant_shopping'}]
    [{$smarty.block.parent}]
[{/if}]