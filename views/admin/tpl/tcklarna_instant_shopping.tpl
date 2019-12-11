[{assign var="lang_tag" value=$languages.$editlanguage->abbr|oxupper}]
[{assign var="previewUrlBase" value=$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/img/')}]

<link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tcklarna_admin2.css')}]">
<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster.bundle.min.css')}]">
<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster-sideTip-light.min.css')}]">
<script type="text/javascript" src="[{$oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/jquery-1.12.4.min.js')}]"></script>
<script type="text/javascript" src="[{$oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/tooltipster.bundle.min.js')}]"></script>
<script type="text/javascript" src="[{$oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/jscolor/jscolor.js')}]"></script>


<div class="[{$box|default:'box'}]" style="[{if !$box && !$bottom_buttons}]height: 100%;[{/if}]">
    <div class="main-container">
        [{include file="tcklarna_header.tpl" title="TCKLARNA_INSTANT_SHOPPING_MENU"|oxmultilangassign desc="TCKLARNA_INSTANT_SHOPPING_HEADER"|oxmultilangassign}]
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
                                                        <label class="label toggle" for="instant-shopping-toggle">
                                                            <input type="hidden" name="confbools[blKlarnaInstantShoppingEnabled]" value="0">
                                                            <input type="checkbox" class="toggle_input radio_type"
                                                                   name="confbools[blKlarnaInstantShoppingEnabled]"
                                                                   value="1" id="instant-shopping-toggle"
                                                                   [{if ($confbools.blKlarnaInstantShoppingEnabled === true)}]checked[{/if}]/>
                                                            <div class="toggle-control" id="instant-shopping-control"></div>
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
                                        <tr class="dark">
                                            <td class="name">[{oxmultilang ident="TCKLARNA_IS_BUTTON_REPLACE" }]</td>
                                            <td class="input w460">
                                                <div class="input">
                                                    <button class="btn-save no-bg" type="button" id='replace-button-key'
                                                            [{if ($confbools.blKlarnaInstantShoppingEnabled === false)}]disabled[{/if}]>[{oxmultilang ident="TCKLARNA_IS_REPLACE" }]</button>
                                                </div>
                                            </td>
                                            <td class="info-block">
                                                <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_IS_BUTTON_REPLACE_TOOLTIP"}]">
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
                                     [{if $confbools.blKlarnaInstantShoppingEnabled === true }]style="display: block"[{/if}]>
                                    <table class="inner">
                                        <tbody>
                                            <tr class="dark">
                                                <td class="name-bold" colspan="3">
                                                    [{oxmultilang ident="TCKLARNA_IS_BUTTON_APEARANCE_HEADLINE"}]
                                                </td>
                                            </tr>
                                            [{assign var="previewPath" value=""}]
                                            [{foreach from=$buttonStyleOptions key="optionName" item="options"}]
                                                <tr class="dark">
                                                    <td class="name">[{oxmultilang ident=$options.label}]</td>
                                                    <td class="input">
                                                        <div class="selector button-style-selector" id="button-style-[{$optionName}]">
                                                            <div class="selector__menu">
                                                                <ul class="selector__choices">
                                                                    [{foreach from=$options.values item="optionValue"}]
                                                                        [{if $confaarrs.aarrKlarnaISButtonStyle.$optionName === $optionValue}]
                                                                            [{assign var="selected" value="--selected"}]
                                                                            [{assign var="previewPath" value=$previewPath|cat:"-"|cat:$optionValue}]
                                                                        [{else}]
                                                                            [{assign var="selected" value=""}]
                                                                        [{/if}]
                                                                        <li class="selector__item[{$selected}]">
                                                                            <a href="#" data-value=[{$optionValue}]>
                                                                                [{$optionValue}]
                                                                            </a>
                                                                        </li>
                                                                    [{/foreach}]
                                                                </ul>
                                                                <input type="hidden" data-button-style-value
                                                                       name="confaarrs[aarrKlarnaISButtonStyle][[{$optionName}]]"
                                                                       value="[{$confaarrs.aarrKlarnaISButtonStyle.$optionName}]"
                                                                />
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="info-block">
                                                        <span class="kl-tooltip"
                                                              title="[{oxmultilang ident="TCKLARNA_IS_"|cat:$optionName|upper|cat:"_TOOLTIP"}]">
                                                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                        </span>
                                                    </td>
                                                </tr>
                                            [{/foreach}]
                                            <tr class="dark">
                                                <td class="name">
                                                    [{oxmultilang ident="TCKLARNA_IS_BUTTON_PREVIEW"}]
                                                </td>
                                                <td class="button-preview" colspan="2">
                                                    <p><klarna-instant-shopping style="pointer-events: none;"/></p>
                                                </td>
                                            </tr>
                                            [{foreach from=$buttonPlacement item="pageName"}]
                                            <tr class="dark">
                                                <td class="name">
                                                    [{oxmultilang ident="TCKLARNA_IS_BUTTON_PLACEMENT_"|cat:$pageName|upper}]
                                                </td>
                                                <td class="input w460">
                                                    <div class="input">
                                                        <div class="display">
                                                            <label class="label toggle" for="button-placement-[{$pageName}]">
                                                                <input type="hidden"
                                                                       name="confaarrs[aarrKlarnaISButtonPlacement][[{$pageName}]]"
                                                                       value="0">
                                                                <input type="checkbox" class="toggle_input"
                                                                       name="confaarrs[aarrKlarnaISButtonPlacement][[{$pageName}]]"
                                                                       value="1" id="button-placement-[{$pageName}]"
                                                                       [{if $confaarrs.aarrKlarnaISButtonPlacement.$pageName}]checked[{/if}]/>
                                                                <div class="toggle-control" id="toggle-button-placement-[{$pageName}]"></div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="info-block">
                                                    <span class="kl-tooltip"
                                                          title="[{oxmultilang ident="TCKLARNA_IS_BUTTON_PLACEMENT_"|cat:$pageName|upper|cat:"_TOOLTIP"}]">
                                                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                    </span>
                                                </td>
                                            </tr>
                                            [{/foreach}]
                                        </tbody>
                                    </table>
                                    <table class="inner">
                                        <tbody>
                                            <tr class="dark">
                                                <td class="name-bold" colspan="3">[{oxmultilang ident="TCKLARNA_IS_BUTTON_SETTINGS_HEADLINE" }]</td>
                                            </tr>
                                            <tr class="dark">
                                                <td class="conf-label-2">[{ oxmultilang ident="TCKLARNA_SET_TAC_URI" }]</td>
                                                <td class="lang-input">
                                                    [{assign var="confVarName" value="sKlarnaInstantTermsConditionsURI_"|cat:$lang_tag}]
                                                    <div class="input">
                                                        <input type="text" class="url-input m-lang"
                                                               name="confstrs[sKlarnaInstantTermsConditionsURI_[{$lang_tag}]]"
                                                               value="[{$confstrs.$confVarName}]"
                                                               pattern="^(https://)?([a-zA-Z0-9]([a-zA-ZäöüÄÖÜ0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}.*" required>
                                                    </div>
                                                </td>
                                                <td class="info-block">
                                                <span class="kl-tooltip"
                                                      title="[{oxmultilang ident="TCKLARNA_SET_TAC_URI"}]">
                                                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                </span>
                                                </td>
                                            </tr>
                                            [{foreach from=$buttonSettings item="optionName"}]
                                                <tr class="dark">
                                                    <td class="name">[{oxmultilang ident="TCKLARNA_IS_SETTING_"|cat:$optionName|upper}]</td>
                                                    <td class="input w460">
                                                        <div class="input">
                                                            <div class="display">
                                                                <label class="label toggle" for="toggle-[{$optionName}]">
                                                                    <input type="hidden" name="confaarrs[aarrKlarnaISButtonSettings][[{$optionName}]]" value="0">
                                                                    <input type="checkbox" class="toggle_input radio_type"
                                                                           name="confaarrs[aarrKlarnaISButtonSettings][[{$optionName}]]"
                                                                           value="1" id="toggle-[{$optionName}]"
                                                                           [{if $confaarrs.aarrKlarnaISButtonSettings.$optionName === '1'}]checked[{/if}]/>
                                                                    <div class="toggle-control"></div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="info-block">
                                                        <span class="kl-tooltip" title="[{oxmultilang ident="TCKLARNA_IS_SETTING_"|cat:$optionName|upper|cat:"_TOOLTIP"}]">
                                                            <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                                                        </span>
                                                    </td>
                                                </tr>
                                            [{/foreach}]
                                        </tbody>
                                    </table>
                            </td>
                        </tr>

                    </tbody>
                </table>
                <div class="btn-center">
                    <input type="submit" name="save" class="btn-save" value="[{oxmultilang ident="GENERAL_SAVE"}]"
                           id="form-save-button">
                </div>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript" src="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/js/tcklarna_admin_lib.js') }]"></script>
<script type="text/javascript">
    (function(){
        var $form = $('#myedit');
        // instant shopping enable
        $('#instant-shopping-toggle').click(function(){
            var $plane =  $(this).closest('.config-options').find('.rows-wrapper');
            /** radio style toggle switch */
            if(this.checked) {
                $plane.show(400);
                $('#replace-button-key').attr('disabled', false);
            } else {
                $plane.hide(400);
                $('#replace-button-key').attr('disabled', true);
            }
        });

        // replace button key
        $('#replace-button-key').click(function(){

            var enabled = $('#instant-shopping-toggle').prop('checked');

            if(enabled === false) {
                $('#replace-button-key').attr('disabled', true);
                return null;
            }


            $form.get(0).appendChild(
                $('<input>')
                    .attr({name: 'replaceButton', value: '1'})
                    .get(0)
            );
            $form.submit();
        });

        // button style
        var previewUrlBase = "[{$previewUrlBase}]";
        $('.button-style-selector').each(function() {
            new Selector2({
                node: this,
                fromOptions: false,
                emptyOption: false,
                onSelect: function updatePreview(selected) {
                    var $previewPath = $('[data-button-style-value]').map(function() {
                        return this.value;
                    });

                    Klarna.InstantShopping.update({
                        "styling": {
                            "theme": {
                                "variation": $previewPath[0],
                                "tagline": $previewPath[1],
                                "type": $previewPath[2]
                            }
                        },
                    })
                }
            });
        });
    })();
</script>
<script>
    window.klarnaAsyncCallback = function () {
        Klarna.InstantShopping.load([{$previewButtonConfig|@json_encode}]);
    };
</script>
<script src="https://x.klarnacdn.net/instantshopping/lib/v1/lib.js"></script>
<style>
    #replace-button-key:disabled {
        background: #d0d0d0;
        border-color: #d0d0;
        color: #1a1919;
    }
</style>
