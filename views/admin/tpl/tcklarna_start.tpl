<link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tcklarna_admin2.css') }]">
<div class="[{$box|default:'box'}]" style="[{if !$box && !$bottom_buttons}]height: 100%;[{/if}]">
    <div class="start_ext_version">
        <img src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/img/checked_checkbox_green_35.png') }]"> [{$oView->getKlarnaModuleInfo()}]
    </div>
    <div class="klarna-header">
        <div class="w7">
            <h1>[{oxmultilang ident="TCKLARNA_EASY_AND_SECURE_SHOPPING"}]</h1>
            <p>[{oxmultilang ident="TCKLARNA_WELCOME_TO_CONFIGURATION"}]</p>
            <a href="https://www.klarna.com/de/verkaeufer/oxid/" target="_blank">
                <button class="btn-save">[{oxmultilang ident="TCKLARNA_ONBOARDING_FLOW_BUTTON"}]</button>
            </a>
        </div>
        <div class="w5">
            <div class="klarna-logo">
            </div>
        </div>
    </div>
    <hr>
    <h1>
        [{oxmultilang ident="TCKLARNA_CONTACT_OPTIONS"}]
    </h1>
    <table class="start-countries">
        <tr>
            <td>
                <div>
                    <div class="klarna-flag se"></div>
                    <div class="country-info">
                        <strong>[{oxmultilang ident="TCKLARNA_PHONE"}]:</strong> (+46) 08-120 120 30<br>
                        <strong>[{oxmultilang ident="TCKLARNA_EMAIL"}]:</strong> shop@klarna.se<br>
                        <strong>[{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:</strong><br>
                        [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_THURSDAY"}]: 8:00 - 19:00<br>
                        [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 8:30 - 17:00<br>
                        [{oxmultilang ident="TCKLARNA_SATURDAY"}] - [{oxmultilang ident="TCKLARNA_MONDAY"}]: 10:00 - 17:00
                    </div>
                </div>
            </td>
            <td>
                <div>
                    <div class="klarna-flag de"></div>
                    <div class="country-info">
                        <strong>[{oxmultilang ident="TCKLARNA_PHONE"}]:</strong> (+49) 221 669 501 30<br>
                        <strong>[{oxmultilang ident="TCKLARNA_EMAIL"}]:</strong> shop@klarna.de<br>
                        <strong>[{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:</strong><br>
                        [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 8:00 - 17:00<br>
                    </div>
                </div>
            </td>
            <td>
                <div>
                    <div class="klarna-flag nl"></div>
                    <div class="country-info">
                        <strong>[{oxmultilang ident="TCKLARNA_PHONE"}]:</strong> (+31) 20 808 2853<br>
                        <strong>[{oxmultilang ident="TCKLARNA_EMAIL"}]:</strong> webwinkel@klarna.nl<br>
                        <strong>[{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:</strong><br>
                        [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 8:00 - 17:00<br>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div>
                    <div class="klarna-flag at"></div>
                    <div class="country-info">
                        <strong>[{oxmultilang ident="TCKLARNA_PHONE"}]:</strong> (+43) 720 883 820<br>
                        <strong>[{oxmultilang ident="TCKLARNA_EMAIL"}]:</strong> merchant@klarna.at<br>
                        <strong>[{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:</strong><br>
                        [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 8:00 - 17:00<br>
                    </div>
                </div>
            </td>
            <td>
                <div>
                    <div class="klarna-flag no"></div>
                    <div class="country-info">
                        <strong>[{oxmultilang ident="TCKLARNA_PHONE"}]:</strong> (+47) 210 49 600<br>
                        <strong>[{oxmultilang ident="TCKLARNA_EMAIL"}]:</strong> merchant@klarna.no<br>
                        <strong>[{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:</strong><br>
                        [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 8:00 - 17:00<br>
                    </div>
                </div>
            </td>
            <td>
                <div>
                    <div class="klarna-flag fi"></div>
                    <div class="country-info">
                        <strong>[{oxmultilang ident="TCKLARNA_PHONE"}]:</strong> (+358) 09-425 99 773<br>
                        <strong>[{oxmultilang ident="TCKLARNA_EMAIL"}]:</strong> kauppa@klarna.fi<br>
                        <strong>[{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:</strong><br>
                        [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 8:00 - 18:00<br>
                    </div>
                </div>
            </td>
        </tr>
    </table>
    <hr>
    <div class="start_user_guide">
        <div class="user-guide-img">
            <img src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/img/user-guide-icon.png') }]" height="60px" width="60px">
        </div>

        <div class="user-guide-text">
            <h2>[{ "TCKLARNA_USER_GUIDE"|oxmultilangassign }]</h2>
            <p>[{ "TCKLARNA_USER_GUIDE_DESCRIPTION"|oxmultilangassign }]</p>
        </div>

        <div class="user-guide-download">
            <a href="[{$oView->getManualDownloadLink()}]" target="_blank">
                <button class="btn-save">Download</button>
            </a>
        </div>
    </div>
</div>
