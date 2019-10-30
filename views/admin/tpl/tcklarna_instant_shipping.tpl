[{assign var="lang_tag" value=$languages.$editlanguage->abbr|oxupper}]

[{if $readonly }]
    [{assign var="readonly" value="readonly disabled"}]
    [{else}]
    [{assign var="readonly" value=""}]
    [{/if}]

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
        [{include file="tcklarna_header.tpl" title="TCKLARNA_INSTANT_SHIPPING_MENU"|oxmultilangassign desc="TCKLARNA_INSTANT_SHIPPING_HEADER"|oxmultilangassign}]
        <hr>
        <div class="klarna-row">
            <form name="myedit" id="myedit" method="post" action="[{$oViewConf->getSelfLink()}]"
                  enctype="multipart/form-data">
                <input type="hidden" name="MAX_FILE_SIZE" value="[{$iMaxUploadFileSize}]">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="KlarnaInstantShopping">
                <input type="hidden" name="fnc" value="save">
                <table class="config-options">
                    <tbody>
                        <tr class="no-t-border">
                            <td>
                                <table class="inner">
                                    <tbody>
                                    <tr class="dark">
                                        <td class="name-bold" colspan="3">[{oxmultilang ident="TCKLARNA_IS_ENABLED_HEADLINE" }]</td>
                                    </tr>
                                    <tr class="dark">
                                        <td class="name">[{oxmultilang ident="TCKLARNA_IS_ENABLED" }]</td>
                                        <td class="input w460">
                                            <div class="input">
                                                <div class="display">
                                                    <label class="label toggle" for="InstanShippingDisplay">
                                                        <input type="hidden" name="confbools[blKlarnaInstantShippingEnabled]" value="0">
                                                        <input type="checkbox" class="toggle_input radio_type"
                                                               name="confbools[blKlarnaInstantShippingEnabled]"
                                                               value="1" id="InstanShippingDisplay"
                                                               [{if ($confbools.blKlarnaInstantShippingEnabled === true)}]checked[{/if}] [{ $readonly}]/>
                                                        <div class="toggle-control"></div>
                                                    </label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="info-block">
                                            <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_IS_ENABLED_TOOLTIP"}]">
                                                <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                            </span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr class="no-t-border">
                            <td>
                                <div class="rows-wrapper"
                                     [{if $confbools.blKlarnaInstantShippingEnabled === true }]style="display: block"[{/if}]>
                                    <table class="inner">
                                        <tbody>
                                        <tr class="dark">
                                            <td class="name-bold" colspan="3">
                                                [{oxmultilang ident="TCKLARNA_IS_TITLE"}]
                                            </td>
                                        </tr>
                                        <tr class="dark">
                                            <td colspan="2">
                                                [{oxmultilang ident="TCKLARNA_LONG_VERSION"}]
                                            </td>
                                        </tr>
                                        <tr class="dark">
                                            <td class="half">
                                                <input type="radio" id="long-black"
                                                       name="confstrs[sKlarnaFooterValue]" value="longBlack"
                                                       [{ if ($confstrs.sKlarnaFooterValue === 'longBlack') }]checked[{/if}]>
                                                <label class="kl-logo white" for="long-black">
                                                    <klarna-instant-shopping />
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

                    </tbody>
                </table>
                <div class="btn-center">
                    <input type="submit" name="save" class="btn-save" value="[{oxmultilang ident="GENERAL_SAVE"}]"
                           id="form-save-button" [{$readonly}]>
                </div>
            </form>
        </div>
    </div>




    </div>
</div>
<script src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/js/tcklarna_admin_lib.js') }]"></script>
<script>
    (function(){
        $('input.radio_type').click(function(){
            var $choicesPlanes =  $(this).closest('.config-options').find('.rows-wrapper');
            /** radio style toggle switch */
            $(this)
                .closest('table')
                .find('input.radio_type')
                .each(
                    (function(i, e){
                        var $plane = $($choicesPlanes[i]);
                        if(e === this && e.checked) {
                            $plane.show(400)
                                .find('input[type=radio]')[0]
                                .checked = e.checked ? true : false;
                        } else {
                            e.checked = false;
                            $plane.hide(400);
                        }
                    }).bind(this));
        });
    })();
</script>