[{if $oViewConf->isKlarnaPaymentsEnabled() && $oView->loadKlarnaPaymentWidget }]
    [{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]
        [{assign var="is_checked" value=true}]
    [{else}]
        [{assign var="is_checked" value=false}]
    [{/if}]

    [{if $sPaymentID == "klarna_pay_later"}]
        <div class="well well-sm kp-outer">
            <dl>
                <dt>
                    <input class="kp-radio" id="kp-pl" data-payment_id="pay_later" type="radio" name="paymentid"
                           value="[{$sPaymentID}]"
                           [{if $is_checked}]checked[{/if}]>
                    <label for="kp-pl"><b>[{$oView->removeKlarnaPrefix($paymentmethod->oxpayments__oxdesc->value)}]</b></label>
                    <img src="[{$paymentmethod->getBadgeUrl()}]">

                </dt>
                <dt style="font-weight: normal">[{oxmultilang ident="TCKLARNA_PAY_LATER_SUBTITLE"}]</dt>
                <dt>
                    [{if $kpError }]
                        <div class="kp-method alert alert-info"
                             style="[{if !$is_checked}]display: none; [{/if}]max-width:700px">[{ $kpError }]</div>
                    [{else}]
                        <div id="pay_later" class="kp-method" style="display: none;"></div>
                    [{/if}]
                </dt>
            </dl>
        </div>
    [{elseif $sPaymentID == "klarna_slice_it"}]
        <div class="well well-sm kp-outer">
            <dl>
                <dt>
                    <input class="kp-radio" id="kp-pot" data-payment_id="pay_over_time" type="radio"
                           name="paymentid"
                           value="[{$sPaymentID}]"
                           [{if $is_checked}]checked[{/if}]>
                    <label for="kp-pot"><b>[{$oView->removeKlarnaPrefix($paymentmethod->oxpayments__oxdesc->value)}]</b></label>
                    <img src="[{$paymentmethod->getBadgeUrl()}]">
                </dt>
                <dt style="font-weight: normal">[{oxmultilang ident="TCKLARNA_SLICE_IT_SUBTITLE"}]</dt>
                <dt>
                    [{if $kpError }]
                        <div class="kp-method alert alert-info"
                             style="[{if !$is_checked}]display: none; [{/if}]max-width:700px">[{ $kpError }]</div>
                    [{else}]
                        <div id="pay_over_time" class="kp-method" style="display: none;"></div>
                    [{/if}]
                </dt>
            </dl>
        </div>
    [{elseif $sPaymentID == "klarna_directdebit"}]
    <div class="well well-sm kp-outer">
        <dl>
            <dt>
                <input class="kp-radio" id="kp-dd" data-payment_id="direct_debit" type="radio"
                       name="paymentid"
                       value="[{$sPaymentID}]"
                       [{if $is_checked}]checked[{/if}]>
                <label for="kp-dd"><b>[{$oView->removeKlarnaPrefix($paymentmethod->oxpayments__oxdesc->value)}]</b></label>
                <img src="[{$paymentmethod->getBadgeUrl()}]">
            </dt>
            <dt style="font-weight: normal">[{oxmultilang ident="TCKLARNA_PAY_NOW_SUBTITLE"}]</dt>
            <dt>
                [{if $kpError }]
                <div class="kp-method alert alert-info"
                     style="[{if !$is_checked}]display: none; [{/if}]max-width:700px">[{ $kpError }]</div>
                [{else}]
                <div id="direct_debit" class="kp-method" style="display: none;"></div>
                [{/if}]
            </dt>
        </dl>
    </div>
    [{elseif $sPaymentID == "klarna_sofort"}]
    <div class="well well-sm kp-outer">
        <dl>
            <dt>
                <input class="kp-radio" id="kp-so" data-payment_id="direct_bank_transfer" type="radio"
                       name="paymentid"
                       value="[{$sPaymentID}]"
                       [{if $is_checked}]checked[{/if}]>
                <label for="kp-so"><b>[{$oView->removeKlarnaPrefix($paymentmethod->oxpayments__oxdesc->value)}]</b></label>
                <img src="[{$paymentmethod->getBadgeUrl()}]">
            </dt>
            <dt style="font-weight: normal">[{oxmultilang ident="TCKLARNA_PAY_NOW_SUBTITLE"}]</dt>
            <dt>
                [{if $kpError }]
                <div class="kp-method alert alert-info"
                     style="[{if !$is_checked}]display: none; [{/if}]max-width:700px">[{ $kpError }]</div>
                [{else}]
                <div id="direct_bank_transfer" class="kp-method" style="display: none;"></div>
                [{/if}]
            </dt>
        </dl>
    </div>
    [{elseif $sPaymentID == "klarna_pay_now"}]
        <div class="well well-sm kp-outer">
            <dl>
                <dt>
                    <input class="kp-radio" id="kp-pn" data-payment_id="pay_now" type="radio"
                           name="paymentid"
                           value="[{$sPaymentID}]"
                           [{if $is_checked}]checked[{/if}]>
                    <label for="kp-pn"><b>[{$oView->removeKlarnaPrefix($paymentmethod->oxpayments__oxdesc->value)}]</b></label>
                    <img src="[{$paymentmethod->getBadgeUrl()}]">
                </dt>
                <dt style="font-weight: normal">[{oxmultilang ident="TCKLARNA_PAY_NOW_SUBTITLE"}]</dt>
                <dt>
                    [{if $kpError }]
                        <div class="kp-method alert alert-info"
                             style="[{if !$is_checked}]display: none; [{/if}]max-width:700px">[{ $kpError }]</div>
                    [{else}]
                        <div id="pay_now" class="kp-method" style="display: none;"></div>
                    [{/if}]
                </dt>
            </dl>
        </div>
    [{else}]
        [{$smarty.block.parent}]
    [{/if}]
[{else}]
    [{$smarty.block.parent}]
[{/if}]
