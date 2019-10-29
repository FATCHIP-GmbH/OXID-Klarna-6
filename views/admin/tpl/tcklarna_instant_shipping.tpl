[{assign var="lang_tag" value=$languages.$editlanguage->abbr|oxupper}]

[{if $readonly }]
    [{assign var="readonly" value="readonly disabled"}]
    [{else}]
    [{assign var="readonly" value=""}]
    [{/if}]

<link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
<link rel="stylesheet" href="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tcklarna_admin2.css') }]">
<link rel="stylesheet" href="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster.bundle.min.css') }]">
<link rel="stylesheet" href="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster-sideTip-light.min.css') }]">
<script type="text/javascript" src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/jquery-1.12.4.min.js') }]"></script>
<script type="text/javascript"
        src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/tooltipster.bundle.min.js') }]"></script>


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
                                        <td class="name-bold" colspan="3">
                                            [{ oxmultilang ident="TCKLARNA_DISPLAY_IN_FOOTER" }]
                                        </td>
                                    </tr>
                                    <tr class="dark">
                                        <td class="name">
                                            [{ oxmultilang ident="TCKLARNA_FOOTER_PAYMENT_METHODS" }]
                                        </td>
                                        <td class="input w460">
                                            <div class="input">
                                                <div class="display">
                                                    <label class="label toggle" for="FooterDisplay">
                                                        <input type="hidden"
                                                               name="confstrs[sKlarnaInstantDisplay]" value="0">
                                                        <input type="checkbox" class="toggle_input radio_type"
                                                               name="confstrs[sKlarnaInstantDisplay]"
                                                               value="1" id="FooterDisplay"
                                                               [{if ($confstrs.sKlarnaInstantDisplay === '1')}]checked[{/if}] [{ $readonly}]/>
                                                        <div class="toggle-control"></div>
                                                    </label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="info-block">
                                            <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_FOOTER_PAYMENT_METHODS_TOOLTIP"}]">
                                                <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                            </span>
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
<script src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/js/tcklarna_admin_emd.js') }]"></script>