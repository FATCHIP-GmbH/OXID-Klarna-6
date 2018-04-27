[{if $payment->isKlarnaPayment()}]
    <h3 class="underline">[{oxmultilang ident="PAYMENT_METHOD"}]</h3>
    <img src="http:[{$payment->getBadgeUrl()}]"
         style="padding: 0 10px 10px [{if $payment->oxuserpayments__oxpaymentsid->value === 'klarna_checkout'}]0 [{else}]10px[{/if}];[{if $payment->oxuserpayments__oxpaymentsid->value === 'klarna_checkout'}] width: 117px;[{/if}]" width="117">
    <br>
    [{if $payment->oxuserpayments__oxpaymentsid->value === 'klarna_checkout'}]<br>[{/if}]
    <p>
        <b>[{$payment->oxpayments__oxdesc->value}] [{if $basket->getPaymentCosts()}]([{$basket->getFPaymentCosts()}] [{$currency->sign}])[{/if}]</b>
    </p>
    <br>
    <br>
[{else}]
    [{$smarty.block.parent}]
[{/if}]