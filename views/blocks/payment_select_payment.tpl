[{if $oViewConf->isKlarnaPaymentsEnabled() && $oView->loadKlarnaPaymentWidget }]
    [{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]
        [{assign var="is_checked" value=true}]
    [{else}]
        [{assign var="is_checked" value=false}]
    [{/if}]

    [{if $sPaymentID == "klarna"}]
        <div class="well well-sm kp-outer">
            <dl>
                <dt>
                    <input class="kp-radio" id="kp-pl" data-payment_id="pay_later" type="radio" name="paymentid"
                           value="[{$sPaymentID}]"
                           [{if $is_checked}]checked[{/if}]>
                    <label for="kp-pl"><b>[{$oView->removeKlarnaPrefix($paymentmethod->oxpayments__oxdesc->value)}]</b></label>
                    <img src="[{$paymentmethod->getBadgeUrl()}]">

                </dt>
                <dt style="font-weight: normal">[{oxmultilang ident="TCKLARNA_KP_SUBTITLE"}]</dt>
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
    [{else}]
        [{$smarty.block.parent}]
    [{/if}]
[{else}]
    [{$smarty.block.parent}]
[{/if}]
