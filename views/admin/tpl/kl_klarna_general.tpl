[{assign var="lang_tag" value=$languages.$editlanguage->abbr|oxupper}]

[{capture assign="country_creds"}]
    [{include file="kl_country_creds.tpl" }]
[{/capture}]

<script type="text/javascript">
    var kl_countryCredsTemplate = '[{ $country_creds|escape:javascript}]';
    var kl_countriesList = JSON.parse('[{ $kl_countryList }]');
</script>

[{if $readonly }]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]

<link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
<link rel="stylesheet" href="[{ $oViewConf->getKlarnaModuleUrl('out/admin/css/kl_klarna_admin2.css') }]">
<link rel="stylesheet" href="[{ $oViewConf->getKlarnaModuleUrl('out/admin/css/tooltipster.bundle.min.css') }]">
<link rel="stylesheet" href="[{ $oViewConf->getKlarnaModuleUrl('out/admin/css/tooltipster-sideTip-light.min.css') }]">
<script type="text/javascript" src="[{ $oViewConf->getKlarnaModuleUrl('out/js/libs/jquery-1.12.4.min.js') }]"></script>
<script type="text/javascript"
        src="[{ $oViewConf->getKlarnaModuleUrl('out/js/libs/tooltipster.bundle.min.js') }]"></script>

<div class="[{$box|default:'box'}]" style="[{if !$box && !$bottom_buttons}]height: 100%;[{/if}]">
    <div class="main-container">
        [{assign var="tabName" value="KL_BASIC_SETTINGS"|oxmultilangassign }]
        [{include file="kl_header.tpl" title="KL_GENERAL_SETTINGS"|oxmultilangassign}]
        <hr>
        <h4>[{oxmultilang ident="KL_CHOOSE_KLARNA_MODULE_MODE"}]:</h4>

        <form name="myedit" id="myedit" method="post"
              action="[{$oViewConf->getSelfLink()}]"
              enctype="multipart/form-data"
              data-langs="[{$oView->getLangs()}]">
            <input type="hidden" name="MAX_FILE_SIZE" value="[{$iMaxUploadFileSize}]">
            [{$oViewConf->getHiddenSid()}]
            <input type="hidden" name="cl" value="klarna_general">
            <input type="hidden" name="fnc" value="save">

            <div class="products-container">
                <input type="radio" name="confstrs[sKlarnaActiveMode]" id="mode_checkout"
                       [{if $confstrs.sKlarnaActiveMode == 'KCO'}]checked="checked"[{/if}] value="KCO"/>
                <label class="product" for="mode_checkout">
                    <div class="product-button"
                         style="height: 60px; border-radius: 5px; text-align: center; line-height: 60px; font-size: 18px;">
                        <span class="kl-mode-title">[{oxmultilang ident="KL_KLARNA_CHECKOUT_NAME"}]</span>
                    </div>
                    <p class="product-description"
                       style="line-height: 1.619; color: #666;">[{oxmultilang ident="KL_KLARNA_CHECKOUT_DESC"}]</p>
                    <i class="fa fa-check fa-2x" aria-hidden="true"></i>
                </label>
                <input type="radio" name="confstrs[sKlarnaActiveMode]" id="mode_payment"
                       [{if $confstrs.sKlarnaActiveMode == 'KP'}]checked="checked" [{/if}]value="KP"/>
                <label class="product" for="mode_payment">
                    <div class="product-button"
                         style="height: 60px; border-radius: 5px; text-align: center; line-height: 60px; font-size: 18px;">
                        <span class="kl-mode-title">[{oxmultilang ident="KL_KLARNA_PAYMENTS_NAME"}]</span>
                    </div>
                    <p class="product-description"
                       style="line-height: 1.619; color: #666;">[{oxmultilang ident="KL_KLARNA_PAYMENTS_DESC"}]</p>
                    <i class="fa fa-check fa-2x" aria-hidden="true"></i>
                </label>
            </div>

            <div class="klarna-expandable-list">
                <table class="klarna-conf-table">
                    <tr class="bg-grey">
                        <td>Mode:</td>
                        <td>
                            <div class="input">
                                <div class="selector" id="modeSelector">
                                    <div class="selector__menu">
                                        <ul class="selector__choices">
                                            <li class="selector__item[{if $confbools.blIsKlarnaTestMode == 0}]--selected[{/if}]">
                                                <a href="#" data-value="0">Live</a>
                                            </li>
                                            <li class="selector__item[{if $confbools.blIsKlarnaTestMode == 1}]--selected[{/if}]">
                                                <a href="#" data-value="1">Playground</a>
                                            </li>
                                        </ul>
                                        <input type="hidden" name="confbools[blIsKlarnaTestMode]"
                                               value="[{$confbools.blIsKlarnaTestMode}]">
                                    </div>

                                </div>
                            </div>
                        </td>
                        <td>
                        <span class="kl-tooltip" title="[{oxmultilang ident="KL_TEST_MODE_TOOLTIP"}]">
                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                        </span>
                        </td>
                    </tr>
                    <tr>
                        <td>[{oxmultilang ident="KL_MERCHANT_ID"}]:</td>
                        <td>
                            <div class="input">
                                <input type="text" class="" name="confstrs[sKlarnaMerchantId]"
                                       value="[{$confstrs.sKlarnaMerchantId}]">
                            </div>
                        </td>
                        <td>
                        <span class="kl-tooltip" title="[{oxmultilang ident="KL_GLOBAL_MERCHANT_ID_TOOLTIP"}]">
                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                        </span>
                        </td>
                    </tr>
                    <tr>
                        <td>[{oxmultilang ident="KL_PASSWORD"}]:</td>
                        <td>
                            <div class="input">
                                <input type="password" class="" name="confstrs[sKlarnaPassword]"
                                       value="[{$confstrs.sKlarnaPassword}]">
                            </div>
                        </td>
                        <td>
                        <span class="kl-tooltip" title="[{oxmultilang ident="KL_GLOBAL_PASSWORD_TOOLTIP"}]">
                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                        </span>
                        </td>
                    </tr>
                    <tr class="bg-grey [{if !$kl_countryCreds }]hidden[{/if}]" id="ycsc">
                        <td class="center" colspan="3">[{oxmultilang ident="KL_YOUR_COUNTRY_SPECIFIC_CREDS"}]</td>
                    </tr>

                    [{if $kl_countryCreds }]
                        [{foreach from=$kl_countryCreds key=sKey item=aValues}]
                            [{include file="kl_country_creds.tpl" }]
                        [{/foreach}]
                    [{/if}]
                    <tr class="bg-grey2" id="acc-separator">
                        <td colspan="3" style="text-align: center;">
                            <a id="add-country-creds" role="button">
                                <i class="fa fa-plus fa-lg"
                                   aria-hidden="true"></i> [{oxmultilang ident="KL_ADD_COUNTRY_SPECIFIC_CREDS"}]
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="no-padding" colspan="3">
                            <div class="rows-wrapper">
                                <table>
                                    <tbody>
                                    <tr class="accFormRow">
                                        <td>[{oxmultilang ident="KL_COUNTRY"}]:</td>
                                        <td>
                                            <div class="selector" id="accSelector">
                                                <div class="selector__menu">
                                                    <ul class="selector__choices">
                                                        [{ if $kl_notSetUpCountries }]
                                                            [{ foreach from=$kl_notSetUpCountries key=countryISO item=title }]
                                                                <li class="selector__item">
                                                                    <a href="#"
                                                                       data-value=[{ $countryISO }]>[{ $title }]</a>
                                                                </li>
                                                            [{ /foreach }]
                                                        [{/if}]
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                            <span class="kl-tooltip"
                                  title="[{oxmultilang ident="KL_CREDENTIALS_COUNTRY_SELECTOR_TOOLTIP"}]">
                                <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                            </span>
                                        </td>
                                    </tr>
                                    <tr class="accFormRow">
                                        <td>[{oxmultilang ident="KL_MERCHANT_ID"}]:</td>
                                        <td>
                                            <div class="input">
                                                <input type="text" class="" name="" value="">
                                            </div>
                                        </td>
                                        <td>
                            <span class="kl-tooltip"
                                  title="[{oxmultilang ident="KL_CREDENTIALS_COUNTRY_MERCHANT_ID_TOOLTIP"}]">
                                <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                            </span>
                                        </td>
                                    </tr>
                                    <tr class="accFormRow">
                                        <td>[{oxmultilang ident="KL_PASSWORD"}]:</td>
                                        <td>
                                            <div class="input">
                                                <input type="password" class="" name="" value="">
                                            </div>
                                        </td>
                                        <td>
                            <span class="kl-tooltip"
                                  title="[{oxmultilang ident="KL_CREDENTIALS_COUNTRY_PASSWORD_TOOLTIP"}]">
                                <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                            </span>
                                        </td>
                                    </tr>
                                    <tr class="accFormRow">
                                        <td class="center" colspan="3">
                                            <button class="btn-save"
                                                    id="acc-save">[{oxmultilang ident="KL_ADD"}]</button>
                                        </td>
                                    </tr>


                                    </tbody>
                                </table>
                            </div>

                        </td>
                    </tr>
                    <tr>
                        <td>
                            [{oxmultilang ident="KL_ENABLE_LOGGING"}]:
                        </td>
                        <td>
                            <div class="input w356">
                                <div class="display">
                                    <label class="label toggle" for="LoggingEnabled">
                                        <input type="hidden" name="confbools[blKlarnaLoggingEnabled]" value="0">
                                        <input id="LoggingEnabled" type="checkbox" class="toggle_input"
                                               name="confbools[blKlarnaLoggingEnabled]"
                                               value="1"
                                               [{if ($confbools.blKlarnaLoggingEnabled)}]checked[{/if}] [{ $readonly}]/>
                                        <div class="toggle-control"></div>
                                    </label>
                                </div>
                            </div>
                        </td>
                        <td>
                        <span class="kl-tooltip" title="[{oxmultilang ident="KL_ENABLE_LOGGING_TOOLTIP"}]">
                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                        </span>
                        </td>
                    </tr>
                    <tr class="bg-grey no-b-border">
                        <td>
                            [{oxmultilang ident="KL_SEND_ADDITIONAL_PRODUCT_DATA"}]
                        </td>
                        <td>
                        <td>
                        </td>
                    </tr>
                    <tr class="bg-grey no-tb-border">
                        <td class="fw-500">
                            [{oxmultilang ident="KL_PRODUCT_URLS"}]
                        </td>
                        <td>
                            <div class="input w356">
                                <div class="display">
                                    <label class="label toggle" for="SendProductUrls">
                                        <input type="hidden" name="confbools[blKlarnaSendProductUrls]" value="0">
                                        <input type="checkbox" class="toggle_input" id="SendProductUrls"
                                               name="confbools[blKlarnaSendProductUrls]"
                                               value="1"
                                               [{if ($confbools.blKlarnaSendProductUrls)}]checked[{/if}] [{ $readonly}]/>
                                        <div class="toggle-control"></div>
                                    </label>
                                </div>
                            </div>
                        </td>
                        <td>
                        <span class="kl-tooltip" title="[{oxmultilang ident="KL_SEND_PRODUCT_URLS_TOOLTIP"}]">
                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                        </span>
                        </td>
                    </tr>
                    <tr class="bg-grey no-t-border">
                        <td class="fw-500">
                            [{oxmultilang ident="KL_IMAGE_URLS"}]
                        </td>
                        <td>
                            <div class="input w356">
                                <div class="display">
                                    <label class="label toggle" for="SendImageUrls">
                                        <input type="hidden" name="confbools[blKlarnaSendImageUrls]" value="0">
                                        <input type="checkbox" class="toggle_input"
                                               name="confbools[blKlarnaSendImageUrls]"
                                               value="1" id="SendImageUrls"
                                               [{if ($confbools.blKlarnaSendImageUrls)}]checked[{/if}] [{ $readonly}]/>
                                        <div class="toggle-control"></div>
                                    </label>
                                </div>
                            </div>
                        </td>
                        <td>
                        <span class="kl-tooltip" title="[{oxmultilang ident="KL_SEND_PRODUCT_IMAGES_TOOLTIP"}]">
                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                        </span>
                        </td>
                    </tr>
                    <tr class="">
                        <td>
                            [{oxmultilang ident="KL_ENABLE_ANONYMIZATION"}]:
                        </td>
                        <td>
                            <div class="input w356">
                                <div class="display">
                                    <label class="label toggle" for="anonymized">
                                        <input type="hidden" name="confbools[blKlarnaEnableAnonymization]" value="0">
                                        <input id="anonymized" type="checkbox" class="toggle_input"
                                               name="confbools[blKlarnaEnableAnonymization]"
                                               value="1"
                                               [{if ($confbools.blKlarnaEnableAnonymization)}]checked[{/if}] [{ $readonly}]/>
                                        <div class="toggle-control"></div>
                                    </label>
                                </div>
                            </div>
                        </td>
                        <td>
                        <span class="kl-tooltip" title="[{oxmultilang ident="KL_ENABLE_ANONYMIZATION_TOOLTIP"}]">
                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                        </span>
                        </td>
                    </tr>
                    <tr class="b-border">
                        <td colspan="3" style="padding: 0;">
                            <div class="rows-wrapper"
                                 style="[{if ($confbools.blKlarnaEnableAnonymization)}]display: block;[{/if}]">
                                <table>
                                    <tbody>
                                    <tr>
                                        <td class="fw-500">[{ oxmultilang ident="GENERAL_LANGUAGE" }]</td>
                                        <td>
                                            <div class="input">
                                                <div class="selector" id="langSelector">
                                                    <div class="selector__menu">
                                                        <ul class="selector__choices">
                                                            [{foreach from=$languages key=lang item=olang}]
                                                                <li class="selector__item[{if $lang == $editlanguage}]--selected[{/if}]">
                                                                    <a href="#"
                                                                       data-value="[{ $lang }]">[{ $olang->name }]</a>
                                                                </li>
                                                            [{/foreach}]
                                                        </ul>
                                                        <input type="hidden" name="editlanguage" id="editlanguage"
                                                               class="saveinnewlanginput"
                                                               value="[{ $editlanguage }]">
                                                    </div>
                                                </div>
                                            </div>

                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-500">[{ oxmultilang ident="KL_ANONYMIZED_PRODUCT" }]</td>
                                        <td>
                                            <div class="input">
                                                [{assign var="confVarName" value="sKlarnaAnonymizedProductTitle_"|cat:$lang_tag}]
                                                <input id="anonymized-value" type="text" class="" data-default-value=""
                                                       name="confstrs[[{$confVarName}]]"
                                                       value="[{ if $confstrs.$confVarName != ""}][{$confstrs.$confVarName}][{/if}]">
                                            </div>
                                        </td>
                                        <td>
                                            <span class="kl-tooltip"
                                                  title="[{oxmultilang ident="KL_ANONYMIZED_PRODUCT_TOOLTIP"}]">
                                                <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                            </span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <div class="messagebox info">[{"KL_CHANGES_SAVED"|oxmultilangassign}]</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="center" colspan="3">
                            <input type="submit" name="save" value="[{oxmultilang ident="GENERAL_SAVE"}]"
                                   class="btn-save" id="form-save-button" [{$readonly}]>
                        </td>
                    </tr>
                </table>
        </form>
    </div>
</div>
<script src="[{ $oViewConf->getKlarnaModuleUrl('out/admin/js/kl_admin_lib.js') }]"></script>
<script src="[{ $oViewConf->getKlarnaModuleUrl('out/admin/js/kl_admin_general.js') }]"></script>
