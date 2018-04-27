<link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
<link rel="stylesheet" href="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tcklarna_admin2.css') }]">
<link rel="stylesheet" href="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster.bundle.min.css') }]">
<link rel="stylesheet" href="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster-sideTip-light.min.css') }]">
<script type="text/javascript" src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/jquery-1.12.4.min.js') }]"></script>
<script type="text/javascript"
        src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/tooltipster.bundle.min.js') }]"></script>
[{if $sslNotSet }]
[{/if}]

<div class="[{$box|default:'box'}]" style="[{if !$box && !$bottom_buttons}]height: 100%;[{/if}]">

    <script type="text/javascript">
        var tcklarna_countriesList = JSON.parse('[{ $tcklarna_countryList }]');
    </script>

    [{if $readonly }]
        [{assign var="readonly" value="readonly disabled"}]
    [{else}]
        [{assign var="readonly" value=""}]
    [{/if}]

    <div class="main-container">
        [{assign var="tabName" value="TCKLARNA_BASIC_SETTINGS"|oxmultilangassign }]
        [{include file="tcklarna_header.tpl" title="TCKLARNA_CONFIGURATION_KCO"|oxmultilangassign desc="TCKLARNA_CONFIGURATION_KCO_ADMIN_DESC"|oxmultilangassign}]
        <hr>
        [{if $sslNotSet }]
            <br>
            <div class="messagebox danger" style="display: block">
                <strong>[{oxmultilang ident="TCKLARNA_ERROR_SHOP_SSL_NOT_CONFIGURED"}]</strong>
            </div>
            <br>
        [{/if}]
        [{if $KCOinactive }]
            <br>
            <div class="messagebox danger" style="display: block">
                <strong>[{oxmultilang ident="TCKLARNA_ERROR_KCO_INACTIVE"}]</strong>
            </div>
            <br>
        [{/if}]
        <form name="myedit" id="myedit" method="post" action="[{$oViewConf->getSelfLink()}]"
              enctype="multipart/form-data"
              data-error="[{$oView->getErrorMessages()}]"
              data-langs="[{$oView->getLangs()}]">
            <input type="hidden" name="MAX_FILE_SIZE" value="[{$iMaxUploadFileSize}]">
            [{$oViewConf->getHiddenSid()}]
            <input type="hidden" name="cl" value="KlarnaConfiguration">
            <input type="hidden" name="fnc" value="save">

            <table class="klarna-conf-table fix1">
                <tr class="bg-grey">
                    <td>
                        [{oxmultilang ident="TCKLARNA_DEFAULT_SHOP_COUNTRY"}]:
                    </td>
                    <td>
                        <div class="selector" id="defaultCountry">
                            <div class="selector__menu">
                                <ul class="selector__choices">
                                    [{ foreach from=$activeCountries item="oxCountry" name="activeCountris" }]
                                        <li class="selector__item[{if $confstrs.sKlarnaDefaultCountry === $oxCountry->oxcountry__oxisoalpha2->value }]--selected[{/if}]">
                                            <a href="#" data-value=[{ $oxCountry->oxcountry__oxisoalpha2->value }]>
                                                [{ $oxCountry->oxcountry__oxtitle->value }]
                                            </a>
                                        </li>
                                    [{ /foreach }]
                                </ul>
                                <input type="hidden" name="confstrs[sKlarnaDefaultCountry]"
                                       value="[{$confstrs.sKlarnaDefaultCountry}]">
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_DEFAULT_COUNTRY_TOOLTIP"}]">
                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>[{oxmultilang ident="TCKLARNA_ALLOW_SEPARATE_SHIPPING_ADDRESS"}]</td>
                    <td>
                        <div class="input w356">
                            <div class="display">
                                <label class="label toggle kco" for="AllowSeparateDeliveryAddress">
                                    <input type="hidden" name="confbools[blKlarnaAllowSeparateDeliveryAddress]"
                                           value="0">
                                    <input type="checkbox" name="confbools[blKlarnaAllowSeparateDeliveryAddress]"
                                           value="1" id="AllowSeparateDeliveryAddress"
                                           [{if ($confbools.blKlarnaAllowSeparateDeliveryAddress)}]checked[{/if}] [{ $readonly}]>
                                    <div class="toggle-control"></div>
                                </label>
                            </div>
                        </div>
                    </td>
                    <td>
                    <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_ALLOW_SEPARATE_DELIVERY_ADDRESS_TOOLTIP"}]">
                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                    </span>
                    </td>
                </tr>

                <tr class="bg-grey">
                    <td>[{oxmultilang ident="TCKLARNA_PHONE_NUMBER_MANDATORY"}]</td>
                    <td>
                        <div class="input w356">
                            <div class="display">
                                <label class="label toggle kco" for="MandatoryPhone">
                                    <input type="hidden" name="confbools[blKlarnaMandatoryPhone]" value="0">
                                    <input type="checkbox" name="confbools[blKlarnaMandatoryPhone]" value="1"
                                           id="MandatoryPhone"
                                           [{if ($confbools.blKlarnaMandatoryPhone)}]checked[{/if}] [{ $readonly}]>
                                    <div class="toggle-control"></div>
                                </label>
                            </div>
                        </div>
                    </td>
                    <td>
                    <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_MANDATORY_PHONE_TOOLTIP"}]">
                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                    </span>
                    </td>
                </tr>

                <tr>
                    <td>[{oxmultilang ident="TCKLARNA_DATE_OF_BIRTH_MANDATORY"}]</td>
                    <td>
                        <div class="input w356">
                            <div class="display">
                                <label class="label toggle kco" for="MandatoryBirthDate">
                                    <input type="hidden" name="confbools[blKlarnaMandatoryBirthDate]" value="0">
                                    <input type="checkbox" name="confbools[blKlarnaMandatoryBirthDate]" value="1"
                                           id="MandatoryBirthDate"
                                           [{if ($confbools.blKlarnaMandatoryBirthDate)}]checked[{/if}] [{ $readonly}]>
                                    <div class="toggle-control"></div>
                                </label>
                            </div>
                        </div>
                    </td>
                    <td>
                    <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_MANDATORY_BIRTH_DATE_TOOLTIP"}]">
                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                    </span>
                    </td>
                </tr>

                <tr class="bg-grey">
                    <td>[{oxmultilang ident="TCKLARNA_ENABLE_AUTOFOCUS"}]</td>
                    <td>
                        <div class="input w356">
                            <div class="display">
                                <label class="label toggle kco" for="EnableAutofocus">
                                    <input type="hidden" name="confbools[blKlarnaEnableAutofocus]" value="0">
                                    <input type="checkbox" name="confbools[blKlarnaEnableAutofocus]" value="1"
                                           id="EnableAutofocus"
                                           [{if ($confbools.blKlarnaEnableAutofocus)}]checked[{/if}] [{ $readonly}]>
                                    <div class="toggle-control"></div>
                                </label>
                            </div>
                        </div>
                    </td>
                    <td>
                    <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_ENABLE_AUTOFOCUS_TOOLTIP"}]">
                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                    </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="inner-table-wrapper">
                        <table class="inner-table">
                            <tr>
                                <td>[{oxmultilang ident="TCKLARNA_ADD_A_CUSTOM_CHECKBOX"}]</td>
                                <td></td>
                                <td>
                                    <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_CHECKBOX_TOOLTIP"}]">
                                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                    </span>
                                </td>

                            </tr>
                            <tr>
                                [{foreach from=$oView->getKlarnaCheckboxOptions() key=sValue item=sLabel name=checkbox}]
                            <tr>
                                <td class="conf-label-2">[{$sLabel}]</td>
                                <td>
                                    <div class="input w356">
                                        <input type="radio"
                                               name="confstrs[iKlarnaActiveCheckbox]"
                                               id="checkbox_[{$sValue}]"
                                               class="radio-custom"
                                               [{if $oView->getActiveCheckbox() == $sValue}]checked="checked"[{/if}]
                                               value="[{$sValue}]"/>
                                        <label class="radio-custom-label" for="checkbox_[{$sValue}]"></label>
                                    </div>
                                </td>
                                <td>
                                </td>
                            </tr>
                            [{/foreach}]
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr class="bg-grey">
                    <td colspan="3" class="inner-table-wrapper">
                        <table class="inner-table">
                            <tr>
                                <td>[{oxmultilang ident="TCKLARNA_ORDER_VALIDATION"}]</td>
                                <td></td>
                                <td>
                                    <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_VALIDATION_TOOLTIP" }]">
                                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                [{foreach from=$oView->getKlarnaValidationOptions() key=sValue item=sLabel name=validation}]
                            <tr>
                                <td class="conf-label-2">[{$sLabel}]</td>
                                <td>
                                    <div class="input w356">
                                        <input type="radio"
                                               name="confstrs[iKlarnaValidation]"
                                               id="validation_[{$sValue}]"
                                               class="radio-custom"
                                               [{if $oView->getChosenValidation() == $sValue}]checked="checked"[{/if}]
                                               value="[{$sValue}]"/>
                                        <label class="radio-custom-label" for="validation_[{$sValue}]"></label>
                                    </div>
                                </td>
                                <td>
                                </td>
                            </tr>
                            [{/foreach}]
                            </tr>
                        </table>
                    </td>
                </tr>

                [{*if $oView->isGBActiveShopCountry()}]
                    <tr class="bg-grey">
                        <td>
                            <div class="klarna-flag gb"></div>
                            <div class="text-after-flag">
                                [{oxmultilang ident="TCKLARNA_SALUTATION_MANDATORY"}]
                            </div>
                        </td>
                        <td>
                            <div class="input w356">
                                <div class="display">
                                    <label class="label toggle kco">
                                        <input type="hidden" name="confbools[tcklarna_blKlarnaSalutationMandatory]" value="0">
                                        <input type="checkbox" name="confbools[tcklarna_blKlarnaSalutationMandatory]" value="1"
                                               [{if ($confbools.tcklarna_blKlarnaSalutationMandatory)}]checked[{/if}] [{ $readonly}]>
                                        <div class="toggle-control"></div>
                                    </label>
                                </div>
                            </div>
                        </td>
                        <td>
                        <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_SALUTATION_MANDATORY_TOOLTIP"}]">
                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                        </span>
                        </td>
                    </tr>
                [{/if*}]
                [{if $blGermanyActive }]
                    [{*<tr>*}]
                    [{*<td>*}]
                    [{*<div class="klarna-flag de"></div>*}]
                    [{*<div class="text-after-flag">*}]
                    [{*[{oxmultilang ident="TCKLARNA_ENABLE_DHL_PACKSTATION"}]*}]
                    [{*</div>*}]
                    [{*</td>*}]
                    [{*<td>*}]
                    [{*<div class="input w356">*}]
                    [{*<div class="display">*}]
                    [{*<label class="label toggle kco">*}]
                    [{*<input type="hidden" name="confbools[tcklarna_blKlarnaEnableDHLPackstations]" value="0">*}]
                    [{*<input type="checkbox" name="confbools[tcklarna_blKlarnaEnableDHLPackstations]" value="1"*}]
                    [{*[{if ($confbools.tcklarna_blKlarnaEnableDHLPackstations)}]checked[{/if}] [{ $readonly}]>*}]
                    [{*<div class="toggle-control"></div>*}]
                    [{*</label>*}]
                    [{*</div>*}]
                    [{*</div>*}]
                    [{*</td>*}]
                    [{*<td>*}]
                    [{*<span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_ENABLE_DHL_PACKSTATIONS_TOOLTIP"}]">*}]
                    [{*<i class="fa fa-question fa-lg" aria-hidden="true"></i>*}]
                    [{*</span>*}]
                    [{*</td>*}]
                    [{*</tr>*}]
                [{/if}]
                <tr>
                    <td>[{oxmultilang ident="TCKLARNA_ENABLE_PRE_FILLING"}]</td>
                    <td>
                        <div class="input w356">
                            <div class="display">
                                <label class="label toggle kco" for="EnablePreFilling">
                                    <input type="hidden" name="confbools[blKlarnaEnablePreFilling]" value="0">
                                    <input type="checkbox" name="confbools[blKlarnaEnablePreFilling]" value="1"
                                           id="EnablePreFilling"
                                           [{if ($confbools.blKlarnaEnablePreFilling)}]checked[{/if}] [{ $readonly}]>
                                    <div class="toggle-control"></div>
                                </label>
                            </div>
                        </div>
                    </td>
                    <td>
                    <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_ENABLE_PRE_FILLING_TOOLTIP"}]">
                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                    </span>
                    </td>
                </tr>

                [{if $blGermanyActive || $blAustriaActive}]
                    <tr class="bg-grey">
                        <td>
                            [{if $blGermanyActive}]
                                <div class="klarna-flag de"></div>
                            [{/if}]
                            [{if $blAustriaActive}]
                                <div class="klarna-flag at"></div>
                            [{/if}]
                            <div class="text-after-flag">
                                [{oxmultilang ident="TCKLARNA_ENABLE_PREFILL_NOTIFICATION"}]
                            </div>
                        </td>
                        <td>
                            <div class="input w356">
                                <div class="display">
                                    <label class="label toggle kco" for="PreFillNotification">
                                        <input type="hidden" name="confbools[blKlarnaPreFillNotification]" value="0">
                                        <input type="checkbox" name="confbools[blKlarnaPreFillNotification]" value="1"
                                               id="PreFillNotification"
                                               [{if ($confbools.blKlarnaPreFillNotification)}]checked[{/if}] [{ $readonly}]>
                                        <div class="toggle-control"></div>
                                    </label>
                                </div>
                            </div>
                        </td>
                        <td>
                    <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_ENABLE_PREFILL_NOTIFICATION_TOOLTIP"}]">
                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                    </span>
                        </td>
                    </tr>
                [{/if}]
                <tr>
                    <td colspan="3" class="inner-table-wrapper">
                        <table class="mlm5 inner-table">
                            <tr>
                                <td class="saveinnewlangtext">
                                    [{ oxmultilang ident="GENERAL_LANGUAGE" }]
                                </td>
                                <td>
                                    <div class="input">
                                        <div class="selector" id="langSelector">
                                            <div class="selector__menu">
                                                <ul class="selector__choices">
                                                    [{foreach from=$languages key=lang item=olang}]
                                                        <li class="selector__item[{if $lang == $editlanguage}]--selected[{/if}]">
                                                            <a href="#" data-value="[{ $lang }]">[{ $olang->name }]</a>
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
                                <td>
                                </td>
                            </tr>
                            [{ include file="tcklarna_lang_spec_conf.tpl" }]
                        </table>
                    </td>
                </tr>
                [{*<tr>*}]
                [{*<td colspan="3">*}]
                [{*<div class="messagebox warn">[{"TCKLARNA_EMPTY_FIELDS_WARNING"|oxmultilangassign}]</div>*}]
                [{*</td>*}]
                [{*</tr>*}]
                <tr>
                    <td colspan="3">
                        <div class="messagebox info">[{"TCKLARNA_CHANGES_SAVED"|oxmultilangassign}]</div>
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

<script src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/js/tcklarna_admin_lib.js') }]"></script>
<script src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/js/tcklarna_admin_configuration.js') }]"></script>