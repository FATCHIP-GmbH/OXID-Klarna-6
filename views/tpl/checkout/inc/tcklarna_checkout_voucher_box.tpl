<div class="col-sm-[{if $oViewConf->isUserLoggedIn() && ($savedAddresses == false || !$shippingAddressAllowed)}]12[{else}]6[{/if}]">
    [{assign var="couponUsed_1" value=$oViewConf->getShowVouchers()}]
    [{assign var="couponUsed_2" value=$oxcmp_basket->getVoucherDiscValue() }]
    <div class="drop-container [{if $couponUsed_1 && $couponUsed_2 }]active[{/if}]" id="klarnaVouchersWidget">
        <div class="drop-trigger">
            <div class="klarna-label">
                <span class="glyphicon glyphicon-star pull-left" aria-hidden="true"></span>
                <span class="klarna-voucher-label">[{oxmultilang ident="TCKLARNA_OUTSIDE_VOUCHER"}]</span>
                <span class="glyphicon glyphicon-menu-down pull-right" aria-hidden="true"></span>
            </div>
        </div>
        <div class="drop-content" [{if $couponUsed_1 && $couponUsed_2 }]style="display: block;"[{/if}]">
            <div class="voucherData">
                [{include file='tcklarna_checkout_voucher_data.tpl'}]
            </div>
            <div>
                <form name="voucher" action="[{$oViewConf->getSslSelfLink()}]" method="post" role="form">
                    <div class="" id="coupon">
                        <div class="hidden">
                            [{$oViewConf->getHiddenSid()}]
                            <input type="hidden" name="cl" value="KlarnaAjax">
                            <input type="hidden" name="fnc" value="addVoucher">
                            <input type="hidden" name="CustomError" value="basket">
                        </div>

                        <div class="form-group">
                            <label class="req sr-only"
                                   for="input_voucherNr">[{oxmultilang ident="ENTER_COUPON_NUMBER"}]</label>

                            <input type="text" name="voucherNr" size="30"
                                   class="form-control"
                                   id="input_voucherNr"
                                   placeholder="[{oxmultilang ident="ENTER_COUPON_NUMBER"}]"
                                   required="required">
                        </div>
                        <div class="form-group">

                            <button type="submit" id="submitVoucher"
                                    class="btn btn-primary">[{oxmultilang ident="REDEEM_COUPON"}]
                            </button>
                            <div class="help-block"></div>
                        </div>
                        <div class="help-block"></div>
                    </div>
                </form>
            </div>
    </div>
</div>
</div>