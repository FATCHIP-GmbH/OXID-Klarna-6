<link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tcklarna_admin2.css')}]">
<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster.bundle.min.css')}]">
<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tooltipster-sideTip-light.min.css')}]">
<script type="text/javascript" src="[{$oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/jquery-1.12.4.min.js')}]"></script>
<script type="text/javascript"
        src="[{$oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/tooltipster.bundle.min.js')}]"></script>

<div class="[{$box|default:'box'}]" style="[{if !$box && !$bottom_buttons}]height: 100%;[{/if}]">

    [{if $readonly}]
        [{assign var="readonly" value="readonly disabled"}]
    [{else}]
        [{assign var="readonly" value=""}]
    [{/if}]

    <div class="main-container">
        <div class="messagebox info" style="display: block">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
            </svg>
            [{oxmultilang ident="TCKLARNA_KP_OPTIONS_REMOVAL"}]
        </div>

        [{assign var="tabName" value="TCKLARNA_BASIC_SETTINGS"|oxmultilangassign}]
        [{include file="tcklarna_header.tpl" title="TCKLARNA_CONFIGURATION_KP"|oxmultilangassign desc="TCKLARNA_CONFIGURATION_KP_ADMIN_DESC"|oxmultilangassign}]

        <hr>
        <h2>[{oxmultilang ident="TCKLARNA_CHOOSE_KP_OPTIONS"}]</h2>

        <form name="myedit" id="myedit" method="post" action="[{$oViewConf->getSelfLink()}]"
              enctype="multipart/form-data">
            <input type="hidden" name="MAX_FILE_SIZE" value="[{$iMaxUploadFileSize}]">
            [{$oViewConf->getHiddenSid()}]
            <input type="hidden" name="cl" value="KlarnaConfiguration">
            <input type="hidden" name="fnc" value="save">

            <div class="kp-options equalHMWrap eqWrap">
                <input type="hidden" name="kpMethods[klarna_pay_later]" value="0"/>
                <input id="pay_later" type="checkbox" name="kpMethods[klarna_pay_later]"
                       [{if $aKPMethods.klarna_pay_later == '1'}]checked="checked" [{/if}]value="1"/>
                <label class="kp-option equalHM eq" for="pay_later">
                    <div class="kp-option__top">
                        <div class="kp-option__top-img">
                            <img src="https://x.klarnacdn.net/payment-method/assets/badges/generic/klarna.svg">
                        </div>
                    </div>
                    <div class="kp-option__bottom">
                        <i class="fa fa-check fa-2x" aria-hidden="true"></i>
                        <h2 class="cl-pink">[{oxmultilang ident="TCKLARNA_PAY_LATER"}]</h2>
                        <div class="kp-option__bottom-text">
                            [{oxmultilang ident="TCKLARNA_PAY_LATER_TEXT"}]
                        </div>
                    </div>
                </label>

                <input type="hidden" name="kpMethods[klarna_slice_it]" value="0"/>
                <input id="slice_it" type="checkbox" name="kpMethods[klarna_slice_it]"
                       [{if $aKPMethods.klarna_slice_it == '1'}]checked="checked" [{/if}]value="1"/>
                <label class="kp-option equalHM eq" for="slice_it">
                    <div class="kp-option__top">
                        <div class="kp-option__top-img">
                            <img src="https://x.klarnacdn.net/payment-method/assets/badges/generic/klarna.svg">
                        </div>
                    </div>
                    <div class="kp-option__bottom">
                        <i class="fa fa-check fa-2x" aria-hidden="true"></i>
                        <h2 class="cl-pink">[{oxmultilang ident="TCKLARNA_SLICE_IT"}]</h2>
                        <div class="kp-option__bottom-text">
                            [{oxmultilang ident="TCKLARNA_SLICE_IT_TEXT"}]
                        </div>
                    </div>
                </label>

                <input type="hidden" name="kpMethods[klarna_pay_now]" value="0"/>
                <input id="klarna_pay_now" type="checkbox" name="kpMethods[klarna_pay_now]"
                       [{if $aKPMethods.klarna_pay_now == '1'}]checked="checked" [{/if}]value="1"/>
                <label class="kp-option equalHM eq" for="klarna_pay_now">

                    <div class="kp-option__top">
                        <div class="kp-option__top-img">
                            <img src="https://x.klarnacdn.net/payment-method/assets/badges/generic/klarna.svg">
                        </div>

                    </div>

                    <div class="kp-option__bottom">
                        <i class="fa fa-check fa-2x" aria-hidden="true"></i>
                        <h2 class="cl-pink">[{oxmultilang ident="TCKLARNA_PAY_NOW"}]</h2>
                        <div class="kp-option__bottom-text">
                            [{oxmultilang ident="TCKLARNA_PAY_NOW_TEXT"}]
                        </div>
                    </div>

                </label>
            </div>
            <div class="center-content">
                <input type="submit" name="save" value="[{oxmultilang ident="GENERAL_SAVE"}]"
                       class="btn-save" id="form-save-button" [{$readonly}]>
            </div>
        </form>
    </div>
</div>