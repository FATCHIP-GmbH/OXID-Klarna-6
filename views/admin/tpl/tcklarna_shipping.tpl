[{assign var="lang_tag" value=$languages.$editlanguage->abbr|oxupper}]

<link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tcklarna_admin2.css')}]">
<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster.bundle.min.css')}]">
<link rel="stylesheet"
      href="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster-sideTip-light.min.css')}]">
<script type="text/javascript"
        src="[{$oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/jquery-1.12.4.min.js')}]"></script>
<script type="text/javascript"
        src="[{$oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/tooltipster.bundle.min.js')}]"></script>


<div class="[{$box|default:'box'}]" style="[{if !$box && !$bottom_buttons}]height: 100%;[{/if}]">
    <div class="main-container">
        [{include file="tcklarna_header.tpl" title="TCKLARNA_SHIPPING_KCO"|oxmultilangassign desc="TCKLARNA_SHIPPING_KCO_DESC"|oxmultilangassign}]
        <hr>
        <div class="klarna-expandable-list">
            <form name="myedit" id="myedit" method="post" action="[{$oViewConf->getSelfLink()}]"
                  enctype="multipart/form-data"
            <input type="hidden" name="MAX_FILE_SIZE" value="[{$iMaxUploadFileSize}]">
            [{$oViewConf->getHiddenSid()}]
            <input type="hidden" name="cl" value="KlarnaShipping">
            <input type="hidden" name="fnc" value="save">

            <table class="klarna-conf-table fix1">
                <tr class="bg-grey">
                    <td colspan="3" class="inner-table-wrapper">
                        [{if $KCOShippingSets|@count > 0}]
                            [{foreach from=$KCOShippingSets key=shippingId item=oDelSet}]
                                <table class="inner">
                                    <tbody>
                                    <tr class="dark">
                                        <td class="conf-label-2">[{$oDelSet->oxdeliveryset__oxtitle->value}]</td>
                                        <td>
                                            <div class="input">
                                                <div class="selector shipping-method-selector">
                                                    <div class="selector__menu">
                                                        <ul class="selector__choices">
                                                            [{foreach from=$KCOShippingMethods key=i item=methodName}]
                                                                <li class="selector__item[{if $confaarrs.aarrKlarnaShippingMap.$shippingId === $methodName}]--selected[{/if}]">
                                                                    <a href="#" data-value="[{$methodName}]">
                                                                        [{$methodName}]
                                                                    </a>
                                                                </li>
                                                            [{/foreach}]
                                                        </ul>
                                                        <input type="hidden"
                                                               name="confaarrs[aarrKlarnaShippingMap][[{$shippingId}]]"
                                                               value="[{$confaarrs.aarrKlarnaShippingMap.$shippingId}]">
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td></td>
                                    </tr>
                                    </tbody>
                                </table>
                            [{/foreach}]
                        [{else}]
                            [{oxmultilang ident="TCKLARNA_PACKSTATION_NO_SHIPPING_SET"}]
                        [{/if}]

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
</div>


<script src="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/js/tcklarna_admin_lib.js')}]"></script>

<script>
[{capture assign="jsCode"}]
$('.shipping-method-selector').each(function () {
    new Selector2({
        node: this,
        fromOptions: false,
        emptyOption: 'keep'
    });
});
[{/capture}]
[{oxscript add=$jsCode}]
</script>
[{oxscript}]