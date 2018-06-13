[{foreach from=$Errors.basket item=oEr key=key}]
    [{if $oEr->getErrorClassType() == 'oxVoucherException'}]
        <div class="alert alert-danger">
            [{oxmultilang ident="COUPON_NOT_ACCEPTED" args=$oEr->getValue('voucherNr')}]
            <strong>[{oxmultilang ident="REASON" suffix="COLON"}]</strong>
            [{$oEr->getOxMessage()}]
        </div>
    [{/if}]
[{/foreach}]
[{foreach from=$Errors.default item=oEr key=key}]
    [{if $oEr->getErrorClassType() == 'oxVoucherException'}]
        <div class="alert alert-danger">
            [{oxmultilang ident="COUPON_NOT_ACCEPTED" args=$oEr->getValue('voucherNr')}]
            <strong>[{oxmultilang ident="REASON" suffix="COLON"}]</strong>
            [{$oEr->getOxMessage()}]
        </div>
    [{/if}]
[{/foreach}]