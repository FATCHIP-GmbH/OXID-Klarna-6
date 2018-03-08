<link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
<link rel="stylesheet" href="[{ $oViewConf->getKlarnaModuleUrl('out/admin/css/kl_klarna_admin2.css') }]">
<link rel="stylesheet" href="[{ $oViewConf->getKlarnaModuleUrl('out/admin/css/tooltipster.bundle.min.css') }]">
<link rel="stylesheet" href="[{ $oViewConf->getKlarnaModuleUrl('out/admin/css/tooltipster-sideTip-light.min.css') }]">
<script type="text/javascript" src="[{ $oViewConf->getKlarnaModuleUrl('out/js/libs/jquery-1.12.4.min.js') }]"></script>
<script type="text/javascript" src="[{ $oViewConf->getKlarnaModuleUrl('out/js/libs/tooltipster.bundle.min.js') }]"></script>

<div class="[{$box|default:'box'}]" style="[{if !$box && !$bottom_buttons}]height: 100%;[{/if}]">
    <div class="main-container">
        [{include file="kl_header.tpl" title="KL_EXTERNAL_PAYMENTS"|oxmultilangassign }]
        <hr>
        [{ if $mode === 'KCO' }]
        <div class="klarna-expandable-list">
            <form name="myedit" id="myedit" method="post" action="[{$oViewConf->getSelfLink()}]" enctype="multipart/form-data">
                <input type="hidden" name="MAX_FILE_SIZE" value="[{$iMaxUploadFileSize}]">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="KlarnaExternalPayments">
                <input type="hidden" name="fnc" value="save">
                [{foreach from=$activePayments item=payment}]
                    [{assign var="paymentOn" value=$payment.klexternalpayment|intval }]
                    [{assign var="checkoutOn" value=$payment.klexternalcheckout|intval }]

                    <div class="klarna-row">
                        <div class="row-label">
                            <div class="sign plus"></div>
                            <div class="text">
                                [{$payment.desc}]
                            </div>
                        </div>
                        <div class="clear"></div>
                            <div class="rows-wrapper">
                                <table class="config-options klarna-conf-table">
                                    <tbody>
                                    <tr>
                                        <td>
                                            <table class="inner-table">
                                                <tr class="bg-light">
                                                    <td class="name-bold">
                                                        [{oxmultilang ident="KL_PAYMENT_METHOD"}]
                                                    </td>
                                                    <td>
                                                        <div class="input">
                                                        <div class="selector payment-selector">
                                                            <div class="selector__menu">
                                                                <ul class="selector__choices">
                                                                    [{foreach from=$paymentNames item=name}]
                                                                        <li class="selector__item[{if $payment.klexternalname == $name}]--selected[{/if}]">
                                                                            <a href="#" data-value="[{$name}]">
                                                                                [{$name}]
                                                                            </a>
                                                                        </li>
                                                                    [{/foreach}]
                                                                </ul>
                                                                <input type="hidden"
                                                                       name="payments[[{$payment.oxid}]][oxpayments__klexternalname]"
                                                                       value="[{$payment.klexternalname}]">
                                                            </div>
                                                        </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="kl-tooltip"
                                                              title="[{oxmultilang ident="KL_EXTERNAL_PAYMENT_NAME_SELECT_TOOLTIP"}]">
                                                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <table class="inner-table">
                                                <tr class="bg-light">
                                                    <td class="name-bold">
                                                        [{oxmultilang ident="KL_EXTERNAL_PAYMENT_METHOD"}]
                                                    </td>
                                                    <td>
                                                        <div class="input">
                                                            <div class="display">
                                                                <label class="label toggle" for="[{$payment.oxid}]">
                                                                    <input type="hidden"
                                                                           name="payments[[{$payment.oxid}]][oxpayments__klexternalpayment]"
                                                                           value="0">
                                                                    <input type="checkbox"
                                                                           class="toggle_input js-external-payment"
                                                                           name="payments[[{$payment.oxid}]][oxpayments__klexternalpayment]"
                                                                           data-payment-id="[{$payment.oxid}]"
                                                                           value="1" id="[{$payment.oxid}]"
                                                                           [{if $paymentOn }]checked[{/if}] [{ $readonly}]>
                                                                    <div class="toggle-control js-enable-payment"
                                                                         data-payment-id="[{$payment.oxid}]"
                                                                         data-column="payment">
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="kl-tooltip"
                                                              title="[{oxmultilang ident="KL_ENABLE_EXTERNAL_PAYMENT_TOOLTIP"}]">
                                                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                        </span>
                                                    </td>
                                                </tr>
                                                [{if $payment.isCheckout }]
                                                    <tr class="bg-light">
                                                        <td class="name-bold">
                                                            [{oxmultilang ident="KL_EXTERNAL_CHECKOUT"}]
                                                        </td>
                                                        <td>
                                                            <div class="input">
                                                                <div class="display">
                                                                    <label class="label toggle" for="[{$payment.oxid}]1">
                                                                        <input type="hidden"
                                                                               name="payments[[{$payment.oxid}]][oxpayments__klexternalcheckout]"
                                                                               value="0">
                                                                        <input type="checkbox"
                                                                               class="toggle_input js-external-payment"
                                                                               name="payments[[{$payment.oxid}]][oxpayments__klexternalcheckout]"
                                                                               data-payment-id="[{$payment.oxid}]"
                                                                               value="1" id="[{$payment.oxid}]1"
                                                                               [{if $checkoutOn }]checked[{/if}] [{ $readonly}]>
                                                                        <div class="toggle-control js-enable-payment"
                                                                             data-payment-id="[{$payment.oxid}]"
                                                                             data-column="checkout">
                                                                        </div>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="kl-tooltip"
                                                                  title="[{oxmultilang ident="KL_ENABLE_EXTERNAL_CHECKOUT_TOOLTIP"}]">
                                                                <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                [{/if}]
                                            </table>
                                        </td>
                                    </tr>
                                    <tr id="urls-[{$payment.oxid}]">
                                        <td>
                                            <div class="rows-wrapper" [{if ($payment.isExternalEnabled == '1')}]style="display: block;"[{/if}]>
                                                <table class="inner-table">
                                                    <tr class="bg-light">
                                                        <td class="name-bold">
                                                            [{ oxmultilang ident="GENERAL_LANGUAGE" }]
                                                        </td>
                                                        <td>
                                                            <div class="selector langSelector" data-payment-id="[{$payment.oxid}]">
                                                                <div class="selector__menu">
                                                                    <ul class="selector__choices">
                                                                        [{foreach from=$languages key=lang item=olang}]
                                                                            <li class="selector__item[{if $lang == $adminlang}]--selected[{/if}]"
                                                                                data-value="[{ $lang }]">
                                                                                <a href="#">[{ $olang->name }]</a>
                                                                            </li>
                                                                        [{/foreach}]
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                        </td>
                                                    </tr>
                                                    <tr class="bg-light">
                                                        <td colspan="3">
                                                            <div class="rows-wrapper"
                                                                 [{ assign var="patternPrefix" value="data-"}]
                                                                 [{if $paymentOn }]
                                                                    style="display: block"
                                                                    [{ assign var="patternPrefix" value=""}]
                                                                [{/if}]>
                                                                <table class="inner-table">
                                                                    <tr class="bg-dark js-payment-img"
                                                                        [{if $adminlang != 0}]
                                                                            [{ assign var="suffix" value="_"|cat:$adminlang }]

                                                                        [{else}]
                                                                            [{ assign var="suffix" value=""}]
                                                                        [{/if}]
                                                                    >
                                                                        <td>
                                                                            <div class="name-bold">[{ oxmultilang ident="KL_IMAGE_URI_EXT_PAYMENT" }]</div>
                                                                            <div class="dimensions-tip">[{ oxmultilang ident="KL_IMAGE_TIP_69x24" }]</div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="input relative">
                                                                                <input type="text" class="js-multilang-input"
                                                                                       [{$patternPrefix}]pattern="^(https://)?([a-zA-Z0-9]([a-zA-ZäöüÄÖÜ0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}.*"
                                                                                       data-payment-id="[{$payment.oxid}]"
                                                                                       data-field-name="oxpayments__klpaymentimageurl"
                                                                                       name="payments[[{$payment.oxid}]][oxpayments__klpaymentimageurl[{$suffix}]]"
                                                                                       [{*[{if $paymentOn}] required [{/if}]*}]
                                                                                       value="[{$payment.klpaymentimageurl}]">
                                                                                [{if $payment.klpaymentimageurl}]
                                                                                    <div class="preview_69_24">
                                                                                        <img src="[{ $oViewConf->resolveFullAssetUrl($payment.klpaymentimageurl) }]"
                                                                                             width="69" height="24">
                                                                                    </div>
                                                                                [{/if}]
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <span class="kl-tooltip"
                                                                                  title="[{oxmultilang ident="KL_EXTERNAL_PAYMENT_IMAGE_URL_TOOLTIP"}]">
                                                                                <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                            [{if $payment.isCheckout }]
                                                                <div class="rows-wrapper"
                                                                    [{ assign var="patternPrefix" value="data-"}]
                                                                    [{if $checkoutOn }]
                                                                        style="display: block"
                                                                    [{ assign var="patternPrefix" value=""}]
                                                                    [{/if}]>
                                                                <table class="inner-table">
                                                                        <tr class="bg-dark js-checkout-img">
                                                                            <td>
                                                                                <div class="name-bold">[{ oxmultilang ident="KL_IMAGE_URI_EXT_CHECKOUT" }]</div>
                                                                                <div class="dimensions-tip">[{ oxmultilang ident="KL_IMAGE_TIP_276x48" }]</div>
                                                                            </td>
                                                                            <td>
                                                                                <div class="input relative">
                                                                                    <input type="text" class="js-multilang-input half-w"
                                                                                           [{$patternPrefix}]pattern="^(https://)?([a-zA-Z0-9]([a-zA-ZäöüÄÖÜ0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}.*"
                                                                                           data-payment-id="[{$payment.oxid}]"
                                                                                           data-field-name="oxpayments__klcheckoutimageurl"
                                                                                           name="payments[[{$payment.oxid}]][oxpayments__klcheckoutimageurl[{$suffix}]]"
                                                                                           [{ if $checkoutOn }] required [{/if}]
                                                                                           value="[{$payment.klcheckoutimageurl}]">

                                                                                    [{if $payment.klcheckoutimageurl}]
                                                                                        <div class="preview_276_48">
                                                                                            <img src="[{ $oViewConf->resolveFullAssetUrl($payment.klcheckoutimageurl) }]"
                                                                                                 width="276" height="48">
                                                                                        </div>
                                                                                    [{/if}]
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                        <span class="kl-tooltip"
                                                                              title="[{oxmultilang ident="KL_EXTERNAL_CHECKOUT_IMAGE_URL_TOOLTIP"}]">
                                                                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                                        </span>
                                                                            </td>
                                                                        </tr>
                                                                </table>
                                                            </div>
                                                            [{/if}]
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        <div class="clear"></div>
                    </div>
                [{/foreach}]
                <div class="messagebox info">
                    <p>[{"KL_CHANGES_SAVED"|oxmultilangassign}]</p>
                </div>
                <div class="btn-center">
                    <input type="submit" name="save" class="btn-save" value="[{oxmultilang ident="GENERAL_SAVE"}]"
                           id="form-save-button" [{$readonly}]>
                </div>
            </form>
            <script src="[{ $oViewConf->getKlarnaModuleUrl('out/admin/js/kl_admin_lib.js') }]"></script>
            <script src="[{ $oViewConf->getKlarnaModuleUrl('out/admin/js/klarna_external_payments.js') }]"></script>
        </div>
        [{ else }]
            [{ "KL_NO_OPTIONS_MODE"|oxmultilangassign }]
        [{/if}]
    </div>
</div>

