<link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
<link rel="stylesheet" href="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tcklarna_admin2.css') }]">
<link rel="stylesheet" href="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster.bundle.min.css') }]">
<link rel="stylesheet"
      href="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster-sideTip-light.min.css') }]">
<script type="text/javascript"
        src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/jquery-1.12.4.min.js') }]"></script>
<script type="text/javascript"
        src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/tooltipster.bundle.min.js') }]"></script>

[{if $readonly }]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]


<div class="[{$box|default:'box'}]" style="[{if !$box && !$bottom_buttons}]height: 100%;[{/if}]">
    <div class="main-container">
        [{include file="tcklarna_header.tpl" title="TCKLARNA_EXTRA_MERCHANT_DATA"|oxmultilangassign desc="TCKLARNA_EXTRA_MERCHANT_DATA_ADMIN_DESC"|oxmultilangassign}]
        <hr>

        <form name="myedit" id="myedit" method="post" action="[{$oViewConf->getSelfLink()}]"
              enctype="multipart/form-data">
            <input type="hidden" name="MAX_FILE_SIZE" value="[{$iMaxUploadFileSize}]">
            [{$oViewConf->getHiddenSid()}]
            <input type="hidden" name="cl" value="KlarnaEmdAdmin">
            <input type="hidden" name="fnc" value="save">
            <table class="klarna-conf-table">
                <tr class="bg-grey">
                    <td>[{oxmultilang ident="TCKLARNA_CUSTOMER_ACCOUNT_INFO"}]</td>
                    <td>
                        <div class="input w356">
                            <div class="display">
                                <label class="label toggle" for="EmdCustomerAccountInfo">
                                    <input type="hidden" name="confbools[blKlarnaEmdCustomerAccountInfo]" value="0">
                                    <input type="checkbox" name="confbools[blKlarnaEmdCustomerAccountInfo]" value="1"
                                           id="EmdCustomerAccountInfo"
                                           [{if $confbools.blKlarnaEmdCustomerAccountInfo}]checked[{/if}] [{ $readonly}]>
                                    <div class="toggle-control"></div>
                                </label>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_EMD_CUSTOMER_INFO_TOOLTIP"}]">
                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                        </span>
                    </td>
                </tr>

                <tr>
                    <td>[{oxmultilang ident="TCKLARNA_PAYMENT_HISTORY_FULL"}]</td>
                    <td>
                        <div class="input w356">
                            <div class="display">
                                <label class="label toggle" for="EmdPaymentHistoryFull">
                                    <input type="hidden" name="confbools[blKlarnaEmdPaymentHistoryFull]" value="0">
                                    <input type="checkbox" name="confbools[blKlarnaEmdPaymentHistoryFull]" value="1"
                                           id="EmdPaymentHistoryFull"
                                           [{if $confbools.blKlarnaEmdPaymentHistoryFull}]checked[{/if}] [{ $readonly}]
                                           class="js-payment-history-toggle">
                                    <div class="toggle-control"></div>
                                </label>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_EMD_PAYMENT_HISTORY_FULL_TOOLTIP"}]">
                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="no-padding">
                        <div class="js-payment-history-options rows-wrapper"
                             [{if $confbools.blKlarnaEmdPaymentHistoryFull}]style="display: block"[{/if}]>
                            <div class="klarna-expandable-list bg-grey">
                                [{foreach from=$activePayments item=payment}]
                                    <div class="klarna-row">
                                        <div class="row-label">
                                            <div class="sign plus"></div>
                                            <div class="text text-normal">
                                                [{$payment.desc}]
                                            </div>
                                        </div>
                                        <div class="clear"></div>
                                        <div class="rows-wrapper">
                                            <table class="config-options bg-grey" style="margin-left:20px;">
                                                <tr class="no-t-border">
                                                    <td colspan="3">
                                                        <table class="inner-table bg-light">
                                                            <tr>
                                                                <td colspan="2"
                                                                    class="conf-label-1">[{oxmultilang ident="TCKLARNA_ASSIGN_PAYMENT_METHOD_TYPE"}]</td>
                                                                <td>
                                                                    <span class="kl-tooltip"
                                                                          title="[{oxmultilang ident="TCKLARNA_EMD_PAYMENT_TYPE_TOOLTIP"}]">
                                                                            <i class="fa fa-question fa-lg"
                                                                               aria-hidden="true"></i>
                                                                        </span>
                                                                </td>
                                                            </tr>
                                                            [{foreach from=$oView->getEmdPaymentTypeOptions() key=sValue item=sLabel name=checkbox}]
                                                                <tr>
                                                                    <td class="conf-label-2">[{$sLabel}]</td>
                                                                    <td colspan="2">
                                                                        <div class="input w356">
                                                                            <input type="radio"
                                                                                   name="payments[[{$payment.oxid}]][oxpayments__tcklarna_paymentoption]"
                                                                                   id="radio-[{$sValue}]-[{$payment.oxid}]"
                                                                                   class="radio-custom"
                                                                                   [{if $payment.tcklarna_paymentoption == $sValue}]checked="checked"[{/if}]
                                                                                   value="[{$sValue}]"/>
                                                                            <label class="radio-custom-label"
                                                                                   for="radio-[{$sValue}]-[{$payment.oxid}]">
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            [{/foreach}]
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr class="no-b-border">
                                                    <td colspan="3">
                                                        <table class="inner-table bg-light">
                                                            <tr>
                                                                <td colspan="2"
                                                                    class="conf-label-1">[{oxmultilang ident="TCKLARNA_WHICH_ORDERS_IN_FULL_PAYMENT_HISTORY"}]</td>
                                                                <td>
                                                                    <span class="kl-tooltip"
                                                                          title="[{oxmultilang ident="TCKLARNA_EMD_HISTORY_FULL_TOOLTIP"}]">
                                                                            <i class="fa fa-question fa-lg"
                                                                               aria-hidden="true"></i>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            [{foreach from=$oView->getFullHistoryOrdersOptions() key=sValue item=sLabel name=checkbox}]
                                                                <tr>
                                                                    <td class="conf-label-2">[{$sLabel}]</td>
                                                                    <td colspan="2">
                                                                        <div class="input w356">
                                                                            <input type="radio"
                                                                                   name="payments[[{$payment.oxid}]][oxpayments__tcklarna_emdpurchasehistoryfull]"
                                                                                   id="radio-[{$sValue}]-[{$payment.oxid}]"
                                                                                   class="radio-custom"
                                                                                   [{if $payment.tcklarna_emdpurchasehistoryfull == $sValue}]checked="checked"[{/if}]
                                                                                   value="[{$sValue}]"/>
                                                                            <label class="radio-custom-label"
                                                                                   for="radio-[{$sValue}]-[{$payment.oxid}]">
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            [{/foreach}]

                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                [{/foreach}]
                            </div>
                        </div>
                    </td>
                </tr>
                <tr class="bg-grey">
                    <td>[{oxmultilang ident="TCKLARNA_PASSTHROUGH_FIELD"}]</td>
                    <td>
                        <div class="input w356">
                            <div class="display">
                                <label class="label toggle" for="EmdPassThrough">
                                    <input type="hidden" name="confbools[blKlarnaEmdPassThrough]" value="0">
                                    <input type="checkbox" name="confbools[blKlarnaEmdPassThrough]" value="1"
                                           id="EmdPassThrough"
                                           [{if $confbools.blKlarnaEmdPassThrough}]checked[{/if}] [{ $readonly}]>
                                    <div class="toggle-control"></div>
                                </label>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_EMD_PASSTHROUGH_FIELD_TOOLTIP"}]">
                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                        </span>
                    </td>
                </tr>
            </table>
            <div class="btn-center">
                <input type="submit" name="save" class="btn-save" value="[{oxmultilang ident="GENERAL_SAVE"}]"
                       id="form-save-button" [{$readonly}]>
            </div>
        </form>
    </div>
</div>
<script src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/js/tcklarna_admin_lib.js') }]"></script>
<script src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/js/tcklarna_admin_emd.js') }]"></script>