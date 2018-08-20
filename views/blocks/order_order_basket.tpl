[{$smarty.block.parent}]

[{if $oViewConf->isKlarnaPaymentsEnabled() && $oView->loadKlarnaPaymentWidget }]
    <div class="loading" style="display: none;">Loading&#8230;</div>
    <script>
        var tcKlarnaClientToken = "[{$client_token}]";
    </script>
    [{ oxscript include=$oViewConf->getModuleUrl('tcklarna','out/src/js/tcklarna_payments_handler.js') }]
    [{ oxscript include="https://x.klarnacdn.net/kp/lib/v1/api.js" }]
    [{ oxstyle include=$oViewConf->getModuleUrl('tcklarna','out/src/css/tcklarna_payments.css') }]
[{/if}]