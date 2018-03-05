<link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
<link rel="stylesheet" href="[{ $oViewConf->getKlarnaModuleUrl('out/admin/css/kl_klarna_admin2.css') }]">
<link rel="stylesheet" href="[{ $oViewConf->getKlarnaModuleUrl('out/admin/css/tooltipster.bundle.min.css') }]">
<link rel="stylesheet" href="[{ $oViewConf->getKlarnaModuleUrl('out/admin/css/tooltipster-sideTip-light.min.css') }]">
<script type="text/javascript" src="[{ $oViewConf->getKlarnaModuleUrl('out/js/libs/jquery-1.12.4.min.js') }]"></script>
<script type="text/javascript"
        src="[{ $oViewConf->getKlarnaModuleUrl('out/js/libs/tooltipster.bundle.min.js') }]"></script>

<div class="[{$box|default:'box'}]" style="[{if !$box && !$bottom_buttons}]height: 100%;[{/if}]">

    [{if $readonly }]
        [{assign var="readonly" value="readonly disabled"}]
    [{else}]
        [{assign var="readonly" value=""}]
    [{/if}]

    <div class="main-container">
        [{assign var="tabName" value="KL_BASIC_SETTINGS"|oxmultilangassign }]
        [{include file="kl_header.tpl" title="KL_CONFIGURATION"|oxmultilangassign}]

        <hr>
        <h2>[{oxmultilang ident="KL_CHOOSE_KP_OPTIONS"}]</h2>

        <form name="myedit" id="myedit" method="post" action="[{$oViewConf->getSelfLink()}]"
              enctype="multipart/form-data">
            <input type="hidden" name="MAX_FILE_SIZE" value="[{$iMaxUploadFileSize}]">
            [{$oViewConf->getHiddenSid()}]
            <input type="hidden" name="cl" value="klarna_configuration">
            <input type="hidden" name="fnc" value="save">

            <div class="kp-options equalHMWrap eqWrap">
                <input type="hidden" name="kpMethods[klarna_pay_later]" value="0"/>
                <input id="pay_later" type="checkbox" name="kpMethods[klarna_pay_later]"
                       [{if $aKPMethods.klarna_pay_later == '1'}]checked="checked" [{/if}]value="1"/>
                <label class="kp-option equalHM eq" for="pay_later">
                    <div class="kp-option__top">
                        <div class="kp-option__top-img">
                            <img src="https://cdn.klarna.com/1.0/shared/image/generic/badge/en_gb/pay_later/standard/pink.svg">
                        </div>
                    </div>
                    <div class="kp-option__bottom">
                        <i class="fa fa-check fa-2x" aria-hidden="true"></i>
                        <h2 class="cl-pink">[{oxmultilang ident="KL_PAY_LATER"}]</h2>
                        <div class="kp-option__bottom-text">
                            [{oxmultilang ident="KL_PAY_LATER_TEXT"}]
                        </div>
                    </div>
                </label>

                <input type="hidden" name="kpMethods[klarna_slice_it]" value="0"/>
                <input id="slice_it" type="checkbox" name="kpMethods[klarna_slice_it]"
                       [{if $aKPMethods.klarna_slice_it == '1'}]checked="checked" [{/if}]value="1"/>
                <label class="kp-option equalHM eq" for="slice_it">
                    <div class="kp-option__top">
                        <div class="kp-option__top-img">
                            <img src="https://cdn.klarna.com/1.0/shared/image/generic/badge/en_gb/slice_it/standard/pink.svg">
                        </div>
                    </div>
                    <div class="kp-option__bottom">
                        <i class="fa fa-check fa-2x" aria-hidden="true"></i>
                        <h2 class="cl-pink">[{oxmultilang ident="KL_SLICE_IT"}]</h2>
                        <div class="kp-option__bottom-text">
                            [{oxmultilang ident="KL_SLICE_IT_TEXT"}]
                        </div>
                    </div>
                </label>

                <input type="hidden" name="kpMethods[klarna_pay_now]" value="0"/>
                <input id="klarna_pay_now" type="checkbox" name="kpMethods[klarna_pay_now]"
                       [{if $aKPMethods.klarna_pay_now == '1'}]checked="checked" [{/if}]value="1"/>
                <label class="kp-option equalHM eq" for="klarna_pay_now">

                    <div class="kp-option__top">
                        <div class="kp-option__top-img">
                            <img src="https://cdn.klarna.com/1.0/shared/image/generic/badge/en_gb/pay_now/standard/pink.svg">
                        </div>

                    </div>

                    <div class="kp-option__bottom">
                        <i class="fa fa-check fa-2x" aria-hidden="true"></i>
                        <h2 class="cl-pink">[{oxmultilang ident="KL_PAY_NOW"}]</h2>
                        <div class="kp-option__bottom-text">
                            [{oxmultilang ident="KL_PAY_NOW_TEXT"}]
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