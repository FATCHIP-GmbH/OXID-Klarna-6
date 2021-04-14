<link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tcklarna_admin2.css') }]">
<script type="text/javascript" src="[{$oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/jquery-1.12.4.min.js')}]"></script>

<div class="[{$box|default:'box'}] full-width" style="[{if !$box && !$bottom_buttons}]height: 100%;[{/if}]">
    <div class="klarna-header bgimg">
        <div class="w6">
            <div class="klarna-icon">
            </div>
            <div class="klarna-inner">
                <h1>[{oxmultilang ident="TCKLARNA_EASY_AND_SECURE_SHOPPING"}]</h1>
                <p>[{"TCKLARNA_WELCOME_TO_CONFIGURATION"|oxmultilangassign:$klarnaOxidLink}]</p>
                <div class="top-button-group">
                    <div class="w50per">
                        <a href="https://eu.portal.klarna.com/signup/oxid" target="_blank">
                            <button class="btn-save">[{oxmultilang ident="TCKLARNA_REGISTER_NOW_BUTTON"}]</button>
                        </a>
                    </div>
                    <div class="w50per">
                        <a href="https://www.klarna.com/de/verkaeufer/oxid/" target="_blank">
                            <button class="btn-save no-bg">[{oxmultilang ident="TCKLARNA_LEARN_MORE_BUTTON"}]</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="klarna-header brands">
        <div class="row">
            <div class="w3 p-lr-15">
                <h4>[{oxmultilang ident="TCKLARNA_PAY_LATER."}]</h4>
                <p>[{oxmultilang ident="TCKLARNA_PAY_LATER_START"}]</p>
            </div>
            <div class="w3 p-lr-15">
                <h4>[{oxmultilang ident="TCKLARNA_SLICE_IT"}]</h4>
                <p>[{oxmultilang ident="TCKLARNA_SLICE_IT_START"}]</p>
            </div>
            <div class="w3 p-lr-15">
                <h4>[{oxmultilang ident="TCKLARNA_PAY_NOW."}]</h4>
                <p>[{oxmultilang ident="TCKLARNA_PAY_NOW_START"}]</p>
            </div>
            <div class="w3 p-lr-15">
                <h4>[{oxmultilang ident="TCKLARNA_CHECKOUT"}]</h4>
                <p>[{oxmultilang ident="TCKLARNA_CHECKOUT_START"}]</p>
            </div>
        </div>
    </div>
    <div class="klarna-header info">
        <div class="w6 img-outer">
            <img class="device-img" src="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/img/')}][{oxmultilang ident="TCKLARNA_DEVICE_IMG"}]">
        </div>
        <div class="w6 ml-half">
            <div>
                <h4>[{oxmultilang ident="TCKLARNA_RB_HOW_TO_ACTIVATE"}]</h4>
                <ul>
                    <li>[{oxmultilang ident="TCKLARNA_RB_HOW_TO_ACTIVATE_LIST_ONE"}]</li>
                    <li>[{oxmultilang ident="TCKLARNA_RB_HOW_TO_ACTIVATE_LIST_TWO"}]</li>
                    <li>[{oxmultilang ident="TCKLARNA_RB_HOW_TO_ACTIVATE_LIST_THREE"}]</li>
                    <li>[{oxmultilang ident="TCKLARNA_RB_HOW_TO_ACTIVATE_LIST_FOUR"}]</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="info-outer ml-half">
        <div class="start_user_guide">
            <div class="user-guide-text">
                <p>[{"TCKLARNA_USER_GUIDE_DESCRIPTION"|oxmultilangassign}]</p>
            </div>
            <div class="user-guide-download">
                <a href="[{$oView->getManualDownloadLink()}]" target="_blank">
                    <button class="btn-save">Download</button>
                </a>
            </div>
            <div class="user-guide-country">
                <select id="country" class="form-control" type="text" name="edit[Land]" onchange="$('.start_user_guide .support').find('div').hide();$('.start_user_guide .support').show();$('.start_user_guide .support').find('.country-' + $(this).val().toLowerCase()).show();">
                    <option disabled value="" selected>[{oxmultilang ident="TCKLARNA_RB_MERCHANT_SUPPORT"}]</option>
                    <option value="DE">[{$countries.DE}]</option>
                    <option value="GB">[{$countries.GB}]</option>
                    <option value="AT">[{$countries.AT}]</option>
                    <option value="NO">[{$countries.NO}]</option>
                    <option value="NL">[{$countries.NL}]</option>
                    <option value="FI">[{$countries.FI}]</option>
                    <option value="SE">[{$countries.SE}]</option>
                    <option value="DK">[{$countries.DK}]</option>
                </select>
            </div>
            <div class="support">
                <div class="country-de">
                    [{oxmultilang ident="TCKLARNA_EMAIL"}]: <a href="mailto:shop@klarna.de" title="[{oxmultilang ident="TCKLARNA_EMAIL"}]">shop@klarna.de</a>
                </div>
                <div class="country-gb">
                    [{oxmultilang ident="TCKLARNA_EMAIL"}]: <a href="mailto:merchants@klarna.co.uk" title="[{oxmultilang ident="TCKLARNA_EMAIL"}]">merchants@klarna.co.uk</a>
                </div>
                <div class="country-at">
                    [{oxmultilang ident="TCKLARNA_EMAIL"}]: <a href="mailto:merchants@klarna.at" title="[{oxmultilang ident="TCKLARNA_EMAIL"}]">merchants@klarna.at</a>
                </div>
                <div class="country-no">
                    [{oxmultilang ident="TCKLARNA_EMAIL"}]: <a href="mailto:merchant@klarna.no" title="[{oxmultilang ident="TCKLARNA_EMAIL"}]">merchant@klarna.no</a>
                </div>
                <div class="country-nl">
                    [{oxmultilang ident="TCKLARNA_EMAIL"}]: <a href="mailto:webwinkel@klarna.nl" title="[{oxmultilang ident="TCKLARNA_EMAIL"}]">webwinkel@klarna.nl</a>
                </div>
                <div class="country-fi">
                    [{oxmultilang ident="TCKLARNA_EMAIL"}]: <a href="mailto:kauppa@klarna.com" title="[{oxmultilang ident="TCKLARNA_EMAIL"}]">kauppa@klarna.com</a>
                </div>
                <div class="country-se">
                    [{oxmultilang ident="TCKLARNA_EMAIL"}]: <a href="mailto:merchant@klarna.com" title="[{oxmultilang ident="TCKLARNA_EMAIL"}]">merchant@klarna.com</a>
                </div>
                <div class="country-dk">
                    [{oxmultilang ident="TCKLARNA_EMAIL"}]: <a href="mailto:merchant@klarna.dk" title="[{oxmultilang ident="TCKLARNA_EMAIL"}]">merchant@klarna.dk</a>
                </div>
            </div>
        </div>
    </div>
</div>
