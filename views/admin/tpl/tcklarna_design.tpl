[{assign var="lang_tag" value=$languages.$editlanguage->abbr|oxupper}]

<link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
<link rel="stylesheet" href="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tcklarna_admin2.css') }]">
<link rel="stylesheet" href="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster.bundle.min.css') }]">
<link rel="stylesheet"
      href="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster-sideTip-light.min.css') }]">
<script type="text/javascript"
        src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/jquery-1.12.4.min.js') }]"></script>
<script type="text/javascript"
        src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/tooltipster.bundle.min.js') }]"></script>
<script src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/jscolor/jscolor.js') }]"></script>

<div class="[{$box|default:'box'}]" style="[{if !$box && !$bottom_buttons}]height: 100%;[{/if}]">
    <div class="main-container">
        [{include file="tcklarna_header.tpl" title="TCKLARNA_KLARNADESIGN"|oxmultilangassign desc="TCKLARNA_DESIGN_SETTINGS_ADMIN_DESC"|oxmultilangassign }]
        <hr>
        <div class="klarna-expandable-list">
            <form name="myedit" id="myedit" method="post"
                  action="[{$oViewConf->getSelfLink()}]"
                  enctype="multipart/form-data"
                  data-langs="[{$oView->getLangs()}]">

                <input type="hidden" name="MAX_FILE_SIZE" value="[{$iMaxUploadFileSize}]">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="KlarnaDesign">
                <input type="hidden" name="fnc" value="save">
                <!-- Teaser -->
                <div class="klarna-row">
                    <div class="row-label">
                        <div class="sign plus"></div>
                        <div class="text ">
                            [{ oxmultilang ident="TCKLARNA_TEASER" }]
                        </div>
                    </div>
                    <div class="clear"></div>
                    <div class="rows-wrapper">
                        <table class="config-options">
                            <tbody>
                            <tr class="dark">
                                <td class="name">
                                    [{ oxmultilang ident="TCKLARNA_ENABLE_TEASER" }]
                                </td>
                                <td>
                                    <div class="input">
                                        <div class="display">
                                            <label class="label toggle" for="TeaserActive">
                                                <input type="hidden" name="settings[blKlarnaTeaserActive]" value="0">
                                                <input type="checkbox" class="toggle_input"
                                                       name="settings[blKlarnaTeaserActive]"
                                                       value="1" id="TeaserActive"
                                                       [{if ($settings.blKlarnaTeaserActive)}]checked[{/if}] [{ $readonly}]/>
                                                <div class="toggle-control"></div>
                                            </label>
                                        </div>
                                    </div>
                                </td>
                                <td class="info-block">
                                    <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_ENABLE_TEASER_TOOLTIP"}]">
                                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                    </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="clear"></div>
                </div>
                <!-- Homepage banner -->
                <div class="klarna-row">
                    <div class="row-label">
                        <div class="sign plus"></div>
                        <div class="text ">
                            [{ oxmultilang ident="TCKLARNA_HOMEPAGE_BANNER" }]
                        </div>
                    </div>
                    <div class="clear"></div>
                    <div class="rows-wrapper">
                        <table class="config-options">
                            <tbody>
                            <tr class="dark">
                                <td class="name-bold" colspan="3">
                                    [{ oxmultilang ident="TCKLARNA_BANNER" }]
                                </td>
                            </tr>
                            <tr class="dark">
                                <td class="name">
                                    [{ oxmultilang ident="TCKLARNA_DISPLAY_ON_HOME" }]
                                </td>
                                <td class="w460">
                                    <div class="input">
                                        <div class="display">
                                            <label class="label toggle" for="DisplayBanner">
                                                <input type="hidden" name="confbools[blKlarnaDisplayBanner]" value="0">
                                                <input type="checkbox" class="toggle_input"
                                                       name="confbools[blKlarnaDisplayBanner]"
                                                       value="1" id="DisplayBanner"
                                                       [{if ($confbools.blKlarnaDisplayBanner)}]checked[{/if}] [{ $readonly}]/>
                                                <div class="toggle-control"></div>
                                            </label>
                                        </div>
                                    </div>
                                </td>
                                <td class="info-block">
                                    <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_DISPLAY_ON_HOME_TOOLTIP"}]">
                                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                    </span>
                                </td>
                            </tr>
                            <tr class="dark">
                                <td colspan="3" class="" style="padding: 0;">
                                    <div class="rows-wrapper"
                                         style="[{if ($confbools.blKlarnaDisplayBanner)}]display: block;[{/if}]">
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
                                                                <input type="hidden" name="editlanguage"
                                                                       id="editlanguage"
                                                                       class="saveinnewlanginput"
                                                                       value="[{ $editlanguage }]">
                                                            </div>
                                                        </div>
                                                    </div>

                                                </td>
                                                <td></td>
                                            </tr>
                                            <tr class="dark">
                                                <td class="name">
                                                    [{ oxmultilang ident="TCKLARNA_BANNER_SRC" }]
                                                </td>
                                                [{assign var="varNameBanner" value="sKlarnaBannerSrc_"|cat:$lang_tag}]
                                                [{if $confstrs.$varNameBanner }]
                                                    [{ assign var="sBannerSrc" value=$confstrs.$varNameBanner }]

                                                [{/if}]


                                                <td class="input w460">
                                    <textarea
                                            name="confstrs[[{"sKlarnaBannerSrc_"|cat:$lang_tag}]]"
                                            data-default-value="[{$settings.sDefaultBannerSrc|escape}]"
                                            class="source m-lang">[{ $sBannerSrc }]</textarea>
                                                    <script>

                                                    </script>
                                                </td>
                                                <td class="info-block">


                                                    <span class="kl-tooltip"
                                                          title="[{oxmultilang ident="TCKLARNA_BANNER_SRC_TOOLTIP"}]">
                                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>
                                                    <a href="http://banner.klarna.com/" target="_blank">Klarna Banner
                                                        Portal</a>
                                                </td>
                                                <td></td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="clear"></div>
                </div>
                <!-- Footer -->
                <div class="klarna-row">
                    <div class="row-label">
                        <div class="sign plus"></div>
                        <div class="text ">
                            [{ oxmultilang ident="TCKLARNA_FOOTER" }]
                        </div>
                    </div>
                    <div class="clear"></div>
                    <div class="rows-wrapper">
                        <table class="config-options">
                            <tbody>
                            <tr class="no-t-border">
                                <td>
                                    <table class="inner">
                                        <tbody>
                                        <tr class="dark">
                                            <td class="name-bold" colspan="3">
                                                [{ oxmultilang ident="TCKLARNA_DISPLAY_IN_FOOTER" }]
                                            </td>
                                        </tr>
                                        [{ if ($mode === 'KCO') }]
                                            <tr class="dark">
                                                <td class="name">
                                                    [{ oxmultilang ident="TCKLARNA_FOOTER_PAYMENT_METHODS" }]
                                                </td>
                                                <td class="input w460">
                                                    <div class="input">
                                                        <div class="display">
                                                            <label class="label toggle" for="FooterDisplay">
                                                                <input type="hidden"
                                                                       name="confstrs[sKlarnaFooterDisplay]" value="0">
                                                                <input type="checkbox" class="toggle_input radio_type"
                                                                       name="confstrs[sKlarnaFooterDisplay]"
                                                                       value="1" id="FooterDisplay"
                                                                       [{if ($confstrs.sKlarnaFooterDisplay === '1')}]checked[{/if}] [{ $readonly}]/>
                                                                <div class="toggle-control"></div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="info-block">
                                                    <span class="kl-tooltip"
                                                          title="[{oxmultilang ident="TCKLARNA_FOOTER_PAYMENT_METHODS_TOOLTIP"}]">
                                                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                    </span>
                                                </td>
                                            </tr>
                                        [{/if}]
                                        <tr class="dark">
                                            <td class="name">
                                                [{ oxmultilang ident="TCKLARNA_FOOTER_KLARNA_LOGO" }]
                                            </td>
                                            <td class="input w460">
                                                <div class="input">
                                                    <div class="display">
                                                        <label class="label toggle" for="FooterDisplay1">
                                                            [{ if ($mode === 'KP') }]
                                                                <input type="hidden"
                                                                       name="confstrs[sKlarnaFooterDisplay]" value="0">
                                                            [{/if}]
                                                            <input type="checkbox" class="toggle_input radio_type"
                                                                   name="confstrs[sKlarnaFooterDisplay]"
                                                                   value="2" id="FooterDisplay1"
                                                                   [{if ($confstrs.sKlarnaFooterDisplay === '2')}]checked[{/if}] [{ $readonly}]/>
                                                            <div class="toggle-control"></div>
                                                        </label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="info-block">
                                                <span class="kl-tooltip"
                                                      title="[{oxmultilang ident="TCKLARNA_FOOTER_KLARNA_LOGO_TOOLTIP"}]">
                                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                </span>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            [{ if ($mode === 'KCO') }]
                                <tr class="no-t-border">
                                    <td>
                                        <div class="rows-wrapper"
                                             [{ if $confstrs.sKlarnaFooterDisplay === '1' }]style="display: block"[{/if}]>
                                            <table class="inner">
                                                <tbody>
                                                <tr class="dark">
                                                    <td class="name-bold" colspan="3">
                                                        [{ oxmultilang ident="TCKLARNA_FOOTER_PAYMENT_METHODS" }] [{ oxmultilang ident="TCKLARNA_DESIGN" }]
                                                    </td>
                                                </tr>
                                                <tr class="dark">
                                                    <td colspan="2">
                                                        [{ oxmultilang ident="TCKLARNA_LONG_VERSION" }]
                                                    </td>
                                                </tr>
                                                <tr class="dark">
                                                    <td class="half">
                                                        <input type="radio" id="long-black"
                                                               name="confstrs[sKlarnaFooterValue]" value="longBlack"
                                                               [{ if ($confstrs.sKlarnaFooterValue === 'longBlack') }]checked[{/if}]>
                                                        <label class="kl-logo white" for="long-black">
                                                            <div class="">
                                                                <img src="[{ $locale|string_format:$aKlarnaFooterImgUrls.longBlack }]">
                                                            </div>
                                                        </label>
                                                    </td>
                                                    <td class="half">
                                                        <input type="radio" id="long-white"
                                                               name="confstrs[sKlarnaFooterValue]" value="longWhite"
                                                               [{ if $confstrs.sKlarnaFooterValue == 'longWhite' }]checked[{/if}]>
                                                        <label class="kl-logo black" for="long-white">
                                                            <div class="">
                                                                <img src="[{ $locale|string_format:$aKlarnaFooterImgUrls.longWhite }]">
                                                            </div>
                                                        </label>
                                                    </td>
                                                </tr>
                                                <tr class="dark">
                                                    <td colspan="2">
                                                        [{ oxmultilang ident="TCKLARNA_SHORT_VERSION" }]
                                                    </td>
                                                </tr>
                                                <tr class="dark">
                                                    <td class="half">
                                                        <input type="radio" id="short-black"
                                                               name="confstrs[sKlarnaFooterValue]" value="shortBlack"
                                                               [{ if $confstrs.sKlarnaFooterValue == 'shortBlack' }]checked[{/if}]>
                                                        <label class="kl-logo white" for="short-black">
                                                            <div class="">
                                                                <img src="[{ $locale|string_format:$aKlarnaFooterImgUrls.shortBlack }]">
                                                            </div>
                                                        </label>
                                                    </td>
                                                    <td class="half">
                                                        <input type="radio" id="short-white"
                                                               name="confstrs[sKlarnaFooterValue]" value="shortWhite"
                                                               [{ if $confstrs.sKlarnaFooterValue == 'shortWhite' }]checked[{/if}]>
                                                        <label class="kl-logo black" for="short-white">
                                                            <div class="">
                                                                <img src="[{ $locale|string_format:$aKlarnaFooterImgUrls.shortWhite }]">
                                                            </div>
                                                        </label>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                    </td>
                                </tr>
                            [{/if}]
                            <tr class="no-t-border no-b-border">
                                <td>
                                    <div class="rows-wrapper"
                                         [{ if $confstrs.sKlarnaFooterDisplay === '2' }]style="display: block"[{/if}]>
                                        <table class="inner">
                                            <tbody>
                                            <tr class="dark">
                                                <td class="name-bold" colspan="3">
                                                    [{ oxmultilang ident="TCKLARNA_FOOTER_KLARNA_LOGO" }] [{ oxmultilang ident="TCKLARNA_DESIGN" }]
                                                </td>
                                            </tr>
                                            <tr class="dark">
                                                <td class="half">
                                                    [{ oxmultilang ident="TCKLARNA_BLACK" }]
                                                </td>
                                                <td class="half">
                                                    [{ oxmultilang ident="TCKLARNA_WHITE" }]
                                                </td>
                                            </tr>
                                            <tr class="dark">
                                                <td class="half">
                                                    <input type="radio" id="logo-black" name="confstrs[sKlarnaFooterValue]"
                                                           value="logoBlack"
                                                           [{ if $confstrs.sKlarnaFooterValue == 'logoBlack' }]checked="checked"[{/if}]>
                                                    <label class="kl-logo white" for="logo-black">
                                                        <div class="kl-logo-inner">
                                                            <img class="" src="[{ $aKlarnaFooterImgUrls.logoBlack }]">
                                                        </div>
                                                    </label>
                                                </td>
                                                <td class="half">
                                                    <input type="radio" id="logo-white" name="confstrs[sKlarnaFooterValue]"
                                                           value="logoWhite"
                                                           [{ if $confstrs.sKlarnaFooterValue == 'logoWhite' }]checked[{/if}]>
                                                    <label class="kl-logo black" for="logo-white">
                                                        <div class="kl-logo-inner">
                                                            <img class="" src="[{ $aKlarnaFooterImgUrls.logoWhite }]">
                                                        </div>
                                                    </label>
                                                </td>
                                            </tr>

                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>

                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Product details page and basket -->
                [{ if $mode === 'KCO' }]
                    <div class="klarna-row">
                        <div class="row-label">
                            <div class="sign plus"></div>
                            <div class="text ">
                                [{ oxmultilang ident="TCKLARNA_DETAILS_AND_BASKET" }]
                            </div>
                        </div>
                        <div class="clear"></div>
                        <div class="rows-wrapper">
                            <table class="config-options">
                                <tbody>
                                [{ assign var="showKPWidgetOptions" value=false }]
                                [{ if $showKPWidgetOptions }]
                                    [{* Klarna Payment Method Widget *}]
                                    <tr class="no-t-border">
                                        <td>
                                            <table class="inner">
                                                <tbody>
                                                <tr class="dark">
                                                    <td class="name-bold" colspan="3">
                                                        [{ oxmultilang ident="TCKLARNA_DISPLAY_PAYMENT_WIDGET" }]
                                                    </td>
                                                </tr>
                                                <tr class="dark">
                                                    <td class="name">
                                                        [{ oxmultilang ident="TCKLARNA_ON_PROD_PAGE" }]
                                                    </td>
                                                    <td class="input w460">
                                                        <div class="input">
                                                            <div class="display">
                                                                <label class="label toggle"
                                                                       for="DisplayWidgetOnProdPage">
                                                                    <input type="hidden"
                                                                           name="confbools[tcklarna_blKlarnaDisplayWidgetOnProdPage]"
                                                                           value="0">
                                                                    <input type="checkbox" class="toggle_input"
                                                                           name="confbools[tcklarna_blKlarnaDisplayWidgetOnProdPage]"
                                                                           value="1"
                                                                           [{if ($confbools.tcklarna_blKlarnaDisplayWidgetOnProdPage)}]checked[{/if}] [{ $readonly}]/>
                                                                    <div class="toggle-control"
                                                                         id="DisplayWidgetOnProdPage"></div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="info-block">
                                                        <span class="kl-tooltip"
                                                              title="[{oxmultilang ident="TCKLARNA_WIDGET_ON_BASKET_PAGE_TOOLTIP"}]">
                                                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr class="dark">
                                                    <td class="name">
                                                        [{ oxmultilang ident="TCKLARNA_ON_BASKET_PAGE" }]
                                                    </td>
                                                    <td class="input w460">
                                                        <div class="input">
                                                            <div class="display">
                                                                <label class="label toggle"
                                                                       for="DisplayWidgetOnBasketPage">
                                                                    <input type="hidden"
                                                                           name="confbools[tcklarna_blKlarnaDisplayWidgetOnBasketPage]"
                                                                           value="0">
                                                                    <input type="checkbox" class="toggle_input"
                                                                           name="confbools[tcklarna_blKlarnaDisplayWidgetOnBasketPage]"
                                                                           value="1" id="DisplayWidgetOnBasketPage"
                                                                           [{if ($confbools.tcklarna_blKlarnaDisplayWidgetOnBasketPage)}]checked[{/if}] [{ $readonly}]/>
                                                                    <div class="toggle-control"></div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="info-block">
                                                        <span class="kl-tooltip"
                                                              title="[{oxmultilang ident="TCKLARNA_WIDGET_ON_PROD_PAGE_TOOLTIP"}]">
                                                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                        </span>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                [{/if}]

                                [{ if $mode === 'KCO' }]
                                    <tr class="no-t-border no-b-border">
                                        <td>
                                            <table class="inner">
                                                <tbody>
                                                <tr class="dark">
                                                    <td class="name-bold" colspan="3">
                                                        [{ oxmultilang ident="TCKLARNA_DISPLAY_BUY_NOW" }]
                                                    </td>
                                                </tr>
                                                <tr class="dark">
                                                    <td class="name">
                                                        [{ oxmultilang ident="TCKLARNA_ON_PROD_PAGE" }]
                                                    </td>
                                                    <td class="input w460">
                                                        <div class="input">
                                                            <div class="display">
                                                                <label class="label toggle" for="DisplayBuyNow">
                                                                    <input type="hidden"
                                                                           name="confbools[blKlarnaDisplayBuyNow]"
                                                                           value="0">
                                                                    <input type="checkbox" class="toggle_input"
                                                                           name="confbools[blKlarnaDisplayBuyNow]"
                                                                           value="1" id="DisplayBuyNow"
                                                                           [{if ($confbools.blKlarnaDisplayBuyNow)}]checked[{/if}] [{ $readonly}]/>
                                                                    <div class="toggle-control"></div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="info-block">
                                                <span class="kl-tooltip"
                                                      title="[{oxmultilang ident="TCKLARNA_BUY_NOW_ON_PROD_PAGE_TOOLTIP"}]">
                                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                </span>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                [{ elseif !$showKPWidgetOptions }]
                                    <tr class="no-t-border no-b-border">
                                        <td>
                                            [{ "TCKLARNA_NO_OPTIONS_MODE"|oxmultilangassign }]
                                        </td>
                                    </tr>
                                [{/if}]
                                </tbody>
                            </table>
                        </div>
                    </div>
                [{/if}]

                <!-- Design options -->
                <div class="klarna-row">
                    <div class="row-label">
                        <div class="sign plus"></div>
                        <div class="text ">
                            [{ if ($mode === 'KP') }]
                                [{ oxmultilang ident="TCKLARNA_PAYMENT_DESIGN" }]
                            [{ elseif ($mode === 'KCO') }]
                                [{ oxmultilang ident="TCKLARNA_CHECKOUT_DESIGN" }]
                            [{/if}]
                        </div>
                    </div>
                    <div class="clear"></div>
                    <div class="rows-wrapper">
                        <table class="config-options btm-bordered">
                            <tbody>
                            <tr class="dark">
                                <td class="name-bold" colspan="3">
                                    [{ oxmultilang ident="TCKLARNA_COLOR_SETTINGS" }]
                                </td>
                            </tr>
                            [{ if ($mode === 'KP') }]
                                <tr class="dark">
                                    <td class="name">
                                        [{ oxmultilang ident="TCKLARNA_BORDER" }]
                                    </td>
                                    <td class="w460">
                                        <div class="input color-picker">
                                            <input class="color {hash:true,required:false}"
                                                   name="confaarrs[aKlarnaDesignKP][color_border]"
                                                   value="[{ $confaarrs.aKlarnaDesignKP.color_border }]">
                                        </div>
                                    </td>
                                    <td class="info-block">
                                <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_BORDER_TOOLTIP"}]">
                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                </span>
                                    </td>
                                </tr>
                                <tr class="dark">
                                    <td class="name">
                                        [{ oxmultilang ident="TCKLARNA_BORDER_SELECTED" }]
                                    </td>
                                    <td class="input w460">
                                        <div class="color-picker">
                                            <input class="color {hash:true,required:false}"
                                                   name="confaarrs[aKlarnaDesignKP][color_border_selected]"
                                                   value="[{ $confaarrs.aKlarnaDesignKP.color_border_selected }]">
                                        </div>
                                    </td>
                                    <td class="info-block">
                                <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_BORDER_SELECTED_TOOLTIP"}]">
                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                </span>
                                    </td>
                                </tr>
                            [{/if }]
                            <tr class="dark">
                                <td class="name">
                                    [{ oxmultilang ident="TCKLARNA_BUTTON" }]
                                </td>
                                <td class="input w460">
                                    <div class="color-picker">
                                        <input class="color {hash:true,required:false}"
                                               name="confaarrs[aKlarnaDesign][color_button]"
                                               value="[{ $confaarrs.aKlarnaDesign.color_button }]">
                                    </div>
                                </td>
                                <td class="info-block">
                                <span class="kl-tooltip"
                                      title="[{ if $mode === 'KCO' }][{oxmultilang ident="TCKLARNA_KCO_BUTTON_TOOLTIP"}][{else}][{oxmultilang ident="TCKLARNA_BUTTON_TOOLTIP"}][{/if}]">
                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                </span>
                                </td>
                            </tr>
                            <tr class="dark">
                                <td class="name">
                                    [{ oxmultilang ident="TCKLARNA_BUTTON_TEXT" }]
                                </td>
                                <td class="input w460">
                                    <div class="color-picker">
                                        <input class="color {hash:true,required:false}"
                                               name="confaarrs[aKlarnaDesign][color_button_text]"
                                               value="[{ $confaarrs.aKlarnaDesign.color_button_text }]">
                                    </div>
                                </td>
                                <td class="info-block">
                                <span class="kl-tooltip"
                                      title="[{ if $mode === 'KCO' }][{oxmultilang ident="TCKLARNA_KCO_BUTTON_TEXT_TOOLTIP"}][{else}][{oxmultilang ident="TCKLARNA_BUTTON_TEXT_TOOLTIP"}][{/if}]">
                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                </span>
                                </td>
                            </tr>
                            <tr class="dark">
                                <td class="name">
                                    [{ oxmultilang ident="TCKLARNA_CHECKBOX" }]
                                </td>
                                <td class="input w460">
                                    <div class="color-picker">
                                        <input class="color {hash:true,required:false}"
                                               name="confaarrs[aKlarnaDesign][color_checkbox]"
                                               value="[{ $confaarrs.aKlarnaDesign.color_checkbox }]">
                                    </div>
                                </td>
                                <td class="info-block">
                                <span class="kl-tooltip"
                                      title="[{ if $mode === 'KCO' }][{oxmultilang ident="TCKLARNA_KCO_DESIGN_CHECKBOX_TOOLTIP"}][{else}][{oxmultilang ident="TCKLARNA_DESIGN_CHECKBOX_TOOLTIP"}][{/if}]">
                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                </span>
                                </td>
                            </tr>
                            <tr class="dark">
                                <td class="name">
                                    [{ oxmultilang ident="TCKLARNA_CHECKBOX_CHECKMARK" }]
                                </td>
                                <td class="input w460">
                                    <div class="color-picker">
                                        <input class="color {hash:true,required:false}"
                                               name="confaarrs[aKlarnaDesign][color_checkbox_checkmark]"
                                               value="[{ $confaarrs.aKlarnaDesign.color_checkbox_checkmark }]">
                                    </div>
                                </td>
                                <td class="info-block">
                                <span class="kl-tooltip"
                                      title="[{ if $mode === 'KCO' }][{oxmultilang ident="TCKLARNA_KCO_DESIGN_CHECKBOX_CHECKMARK_TOOLTIP"}][{else}][{oxmultilang ident="TCKLARNA_DESIGN_CHECKBOX_CHECKMARK_TOOLTIP"}][{/if}]">
                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                </span>
                                </td>
                            </tr>
                            [{ if ($mode === 'KP') }]
                                <tr class="dark">
                                    <td class="name">
                                        [{ oxmultilang ident="TCKLARNA_DETAILS" }]
                                    </td>
                                    <td class="input w460">
                                        <div class="color-picker">
                                            <input class="color {hash:true,required:false}"
                                                   name="confaarrs[aKlarnaDesignKP][color_details]"
                                                   value="[{ $confaarrs.aKlarnaDesignKP.color_details }]">
                                        </div>
                                    </td>
                                    <td class="info-block">
                                <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_DETAILS_TOOLTIP"}]">
                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                </span>
                                    </td>
                                </tr>
                            [{/if}]
                            <tr class="dark">
                                <td class="name">
                                    [{ oxmultilang ident="TCKLARNA_HEADER" }]
                                </td>
                                <td class="input w460">
                                    <div class="color-picker">
                                        <input class="color {hash:true,required:false}"
                                               name="confaarrs[aKlarnaDesign][color_header]"
                                               value="[{ $confaarrs.aKlarnaDesign.color_header }]">
                                    </div>
                                </td>
                                <td class="info-block">
                                <span class="kl-tooltip"
                                      title="[{ if $mode === 'KCO' }][{oxmultilang ident="TCKLARNA_KCO_HEADER_TOOLTIP"}][{else}][{oxmultilang ident="TCKLARNA_HEADER_TOOLTIP"}][{/if}]">
                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                </span>
                                </td>
                            </tr>
                            <tr class="dark">
                                <td class="name">
                                    [{ oxmultilang ident="TCKLARNA_LINK" }]
                                </td>
                                <td class="input w460">
                                    <div class="color-picker">
                                        <input class="color {hash:true,required:false}"
                                               name="confaarrs[aKlarnaDesign][color_link]"
                                               value="[{ $confaarrs.aKlarnaDesign.color_link }]">
                                    </div>
                                </td>
                                <td class="info-block">
                                <span class="kl-tooltip"
                                      title="[{ if $mode === 'KCO' }][{oxmultilang ident="TCKLARNA_KCO_LINK_TOOLTIP"}][{else}][{oxmultilang ident="TCKLARNA_LINK_TOOLTIP"}][{/if}]">
                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                </span>
                                </td>
                            </tr>
                            [{ if ($mode === 'KP') }]
                                <tr class="dark">
                                    <td class="name">
                                        [{ oxmultilang ident="TCKLARNA_TEXT" }]
                                    </td>
                                    <td class="input w460">
                                        <div class="color-picker">
                                            <input class="color {hash:true,required:false}"
                                                   name="confaarrs[aKlarnaDesignKP][color_text]"
                                                   value="[{ $confaarrs.aKlarnaDesignKP.color_text }]">
                                        </div>
                                    </td>
                                    <td class="info-block">
                                    <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_TEXT_TOOLTIP"}]">
                                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                    </span>
                                    </td>
                                </tr>
                                <tr class="dark">
                                    <td class="name">
                                        [{ oxmultilang ident="TCKLARNA_SECONDARY_TEXT" }]
                                    </td>
                                    <td class="input w460">
                                        <div class="color-picker">
                                            <input class="color {hash:true,required:false}"
                                                   name="confaarrs[aKlarnaDesignKP][color_text_secondary]"
                                                   value="[{ $confaarrs.aKlarnaDesignKP.color_text_secondary }]">
                                        </div>
                                    </td>
                                    <td class="info-block">
                                <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_SECONDARY_TEXT_TOOLTIP"}]">
                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                </span>
                                    </td>
                                </tr>
                            [{/if}]
                            </tbody>
                        </table>
                        <table class="config-options">
                            <tbody>
                            <tr class="dark">
                                <td class="name-bold" colspan="3">
                                    [{ oxmultilang ident="TCKLARNA_RADIUS_SETTINGS" }]
                                </td>
                            </tr>
                            <tr class="dark">
                                <td class="name">
                                    [{ oxmultilang ident="TCKLARNA_BORDER_RADIUS" }]
                                </td>
                                <td>
                                    <div class="input w460">
                                        <input class="radius small pull-left" maxlength="4"
                                               name="confaarrs[aKlarnaDesign][radius_border]"
                                               style="border-radius: [{$confaarrs.aKlarnaDesign.radius_border}];"
                                               value="[{ $confaarrs.aKlarnaDesign.radius_border }]">
                                        <input class="range small pull-left" type="range" min="0" max="20"
                                               value="[{$confaarrs.aKlarnaDesign.radius_border|substr:0:-2}]"/>
                                    </div>
                                    <script>
                                        (function () {
                                            var $text = $('input.radius');
                                            var $slider = $('input.range');

                                            $text.on('change', function () {
                                                var num = parseInt(this.value.replace(/^[^0-9]+/, ''), 10);
                                                if (num > 20) {
                                                    num = 20;
                                                } else if (isNaN(num)) {
                                                    this.value = '';
                                                    $slider.val(0);

                                                    return;
                                                }
                                                this.value = num + 'px';
                                                $(this).css('border-radius', num + 'px');
                                                $slider.val(num)
                                            });
                                            $slider.on('input', function () {
                                                $text.val(this.value + 'px');
                                                $text.css('border-radius', this.value + 'px');
                                            });
                                        })();
                                    </script>
                                </td>
                                <td class="info-block">
                                    <span class="kl-tooltip"
                                          title=[{ if $mode === 'KCO' }]"[{oxmultilang ident="TCKLARNA_KCO_BORDER_RADIUS_TOOLTIP"}]"[{else}]
                                    "[{oxmultilang ident="TCKLARNA_BORDER_RADIUS_TOOLTIP"}]"[{/if}]>
                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                    </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="messagebox info">
                        <p>[{"TCKLARNA_CHANGES_SAVED"|oxmultilangassign}]</p>
                    </div>
                    <div class="btn-center">
                        <input type="submit" name="save" class="btn-save" value="[{oxmultilang ident="GENERAL_SAVE"}]"
                               id="form-save-button" [{$readonly}]>
                    </div>
            </form>
        </div>
    </div>
</div>
<script src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/js/tcklarna_admin_lib.js') }]"></script>
<script src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/js/tcklarna_design.js') }]"></script>


