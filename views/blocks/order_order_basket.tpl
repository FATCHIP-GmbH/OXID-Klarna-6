[{$smarty.block.parent}]

[{if $oViewConf->isKlarnaPaymentsEnabled() && $oView->loadKlarnaPaymentWidget }]
    <div class="loading" style="display: none;">Loading&#8230;</div>
    <script>
        var clientToken = "[{$client_token}]";
    </script>
    [{ oxscript include=$oViewConf->getModuleUrl('klarna','out/src/js/klarna_payments_handler.js') }]
    [{ oxscript include="https://x.klarnacdn.net/kp/lib/v1/api.js" }]
    [{ oxstyle include=$oViewConf->getModuleUrl('klarna','out/src/css/klarna_payments.css') }]
[{/if}]