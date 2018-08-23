[{$smarty.block.parent}]

[{if $oViewConf->isKlarnaPaymentsEnabled() && $oView->loadKlarnaPaymentWidget }]
    <div class="loading" style="display: none;">Loading&#8230;</div>
    <div class="kp-method alert alert-info tcklarna-message"
         style="display: none; max-width:700px">[{oxmultilang ident="KP_NOT_AVAILABLE_FOR_COMPANIES"}]</div>
    <script>
        var tcKlarnaClientToken = "[{$client_token}]";
        var tcKlarnaIsB2B = "[{$tcKlarnaIsB2B}]";
    </script>
    [{ oxscript include=$oViewConf->getModuleUrl('tcklarna','out/src/js/tcklarna_payments_handler.js') }]
    [{ oxscript include="https://x.klarnacdn.net/kp/lib/v1/api.js" }]
    [{ oxstyle include=$oViewConf->getModuleUrl('tcklarna','out/src/css/tcklarna_payments.css') }]
[{/if}]