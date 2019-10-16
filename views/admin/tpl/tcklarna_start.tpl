<link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('tcklarna', 'out/admin/src/css/tcklarna_admin2.css') }]">
<div class="[{$box|default:'box'}]" style="[{if !$box && !$bottom_buttons}]height: 100%;[{/if}]">
    <div class="start_ext_version">
        <img src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/img/checked_checkbox_green_35.png') }]"> [{$oView->getKlarnaModuleInfo()}]
    </div>
    <div class="klarna-header">
        <div class="w7">
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
        <div class="w5">
            <div class="klarna-logo">
            </div>
        </div>
    </div>
    <hr>
    <div class="klarna-header">
        <div class="w7 pl-10">
            <h1 class="top-0">[{oxmultilang ident="TCKLARNA_EASY"}]</h1>
            <div class="video-box">
                <a href="https://hello.klarna.com/rs/778-XGY-327/images/How_to_OXID.mp4" target="_blank">
                    <img src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/img/video-scene.jpg') }]">
                </a>
            </div>
            <div class="pl-10">
                <div class="start_user_guide">
                    <div class="user-guide-img">
                        <img src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/admin/src/img/171020_Klarna_Icons_100x100_171006_Klarna_icon_100x100_Topic.svg') }]" height="60px" width="60px">
                    </div>

                    <div class="user-guide-text">
                        <p>[{ "TCKLARNA_USER_GUIDE_DESCRIPTION"|oxmultilangassign }]</p>
                    </div>
                    <div class="user-guide-download">
                        <a href="[{$oView->getManualDownloadLink()}]" target="_blank">
                            <button class="btn-save">Download</button>
                        </a>
                    </div>
                </div>
                <h1>[{oxmultilang ident="TCKLARNA_NEED_SUPPORT"}]</h1>
                <h1 class="lower">[{oxmultilang ident="TCKLARNA_REACH_SUPPORT"}]</h1>
                <table class="support">
                    <tr>
                        <td>
                            <h1>[{$countries.DE}]</h1>
                            <div class="">
                                [{oxmultilang ident="TCKLARNA_EMAIL"}]: shop@klarna.de<br>
                                [{oxmultilang ident="TCKLARNA_PHONE"}]: (+49) 221 669 501 30<br>
                                [{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:
                                [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 8:00 - 17:00<br>
                            </div>
                        </td>
                        <td>
                            <h1>[{$countries.GB}]</h1>
                            <div class="">
                                [{oxmultilang ident="TCKLARNA_EMAIL"}]: merchants@klarna.co.uk<br>
                                [{oxmultilang ident="TCKLARNA_PHONE"}]: (+358) 09-425 99 773<br>
                                [{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:
                                [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 8:00 - 17:00<br>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <h1>[{$countries.AT}]</h1>
                            <div class="">
                                [{oxmultilang ident="TCKLARNA_EMAIL"}]: merchants@klarna.at<br>
                                [{oxmultilang ident="TCKLARNA_PHONE"}]: (+43) 720 883 820<br>
                                [{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:
                                [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 8:00 - 17:00<br>
                            </div>
                        </td>
                        <td>
                            <h1>[{$countries.NO}]</h1>
                            <div class="">
                                [{oxmultilang ident="TCKLARNA_EMAIL"}]: merchant@klarna.no<br>
                                [{oxmultilang ident="TCKLARNA_PHONE"}]: (+47) 210 49 600<br>
                                [{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:
                                [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 8:00 - 17:00<br>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <h1>[{$countries.NL}]</h1>
                            <div class="">
                                [{oxmultilang ident="TCKLARNA_EMAIL"}]: webwinkel@klarna.nl<br>
                                [{oxmultilang ident="TCKLARNA_PHONE"}]: (+31) 20 808 2853<br>
                                [{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:
                                [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 8:00 - 17:00<br>
                            </div>
                        </td>
                        <td>
                            <h1>[{$countries.FI}]</h1>
                            <div class="">
                                [{oxmultilang ident="TCKLARNA_EMAIL"}]: kauppa@klarna.com<br>
                                [{oxmultilang ident="TCKLARNA_PHONE"}]: (+358) 09-425 99 773<br>
                                [{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:
                                [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 8:00 - 18:00<br>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <h1>[{$countries.SE}]</h1>
                            <div class="">
                                [{oxmultilang ident="TCKLARNA_EMAIL"}]: merchant@klarna.com<br>
                                [{oxmultilang ident="TCKLARNA_PHONE"}]: (+46) 08-120 120 30<br>
                                [{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:<br>
                                [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_THURSDAY"}]: 8:00 - 19:00<br>
                                [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 8:30 - 17:00<br>
                                [{oxmultilang ident="TCKLARNA_SATURDAY"}] - [{oxmultilang ident="TCKLARNA_MONDAY"}]: 10:00 - 17:00
                            </div>
                        </td>
                        <td style="vertical-align: baseline">
                            <h1>[{$countries.DK}]</h1>
                            <div class="">
                                [{oxmultilang ident="TCKLARNA_EMAIL"}]: merchant@klarna.dk<br>
                                [{oxmultilang ident="TCKLARNA_PHONE"}]: (+45) 69 91 88 83<br>
                                [{oxmultilang ident="TCKLARNA_BUSINESS_HOURS"}]:<br>
                                [{oxmultilang ident="TCKLARNA_MONDAY"}] - [{oxmultilang ident="TCKLARNA_FRIDAY"}]: 09:00 - 17:00<br>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="w5">
            <div class="klarna-brands">
                <div>
                    <div class="w7">
                        <h4>[{oxmultilang ident="TCKLARNA_PAY_NOW."}]</h4>
                        <p>[{oxmultilang ident="TCKLARNA_PAY_NOW_START"}]</p>
                    </div>
                </div>
                <div>
                    <div class="w7">
                        <h4>[{oxmultilang ident="TCKLARNA_PAY_LATER."}]</h4>
                        <p>[{oxmultilang ident="TCKLARNA_PAY_LATER_START"}]</p>
                    </div>
                </div>
                <div>
                    <div class="w7">
                        <h4>[{oxmultilang ident="TCKLARNA_SLICE_IT."}]</h4>
                        <p>[{oxmultilang ident="TCKLARNA_SLICE_IT_START"}]</p>
                    </div>
                </div>
                <div>
                    <div class="w7">
                        <h4>[{oxmultilang ident="TCKLARNA_CHECKOUT"}]</h4>
                        <p>[{oxmultilang ident="TCKLARNA_CHECKOUT_START"}]</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <hr>

</div>
