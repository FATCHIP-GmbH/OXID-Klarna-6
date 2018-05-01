[{if $sPaymentId == 'klarna_checkout'}]
    [{oxmultilang ident="REGISTERED_YOUR_ORDER" args=$klOrder->oxorder__oxordernr->value}]
    <div class="klarna-iframe-container">
        [{$sKlarnaIframe}]
        [{oxscript include=$oViewConf->getModuleUrl('tcklarna','out/src/js/tcklarna_checkout_handler.js') priority=10}]
    </div>
[{else}]
    [{$smarty.block.parent}]
[{/if}]
