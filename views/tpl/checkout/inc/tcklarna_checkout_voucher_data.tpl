[{if $oViewConf->getShowVouchers() && $oxcmp_basket->getVoucherDiscValue() }]
    [{assign var="currency" value=$oView->getActCurrency()}]
    [{foreach from=$oxcmp_basket->getVouchers() item=sVoucher key=key name=Voucher}]
    <div class="couponData">
        <strong>
            <span>
                [{oxmultilang ident="COUPON"}]&nbsp;([{oxmultilang ident="NUMBER_2"}] [{$sVoucher->sVoucherNr}])
            </span>

            <a href="[{$oViewConf->getSslSelfLink()}]&amp;cl=KlarnaAjax&amp;fnc=removeVoucher&amp;voucherId=[{$sVoucher->sVoucherId}]" class="removeFn text-danger" rel="nofollow">
                <i class="fa fa-times"></i>
                [{oxmultilang ident="REMOVE"}]
            </a>

            <span id="voucherAmount">
                [{oxprice price=$sVoucher->dVoucherdiscount*-1 currency=$currency }]
            </span>
        </strong>
    </div>
    [{/foreach}]
[{/if}]