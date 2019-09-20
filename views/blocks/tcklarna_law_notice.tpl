[{$smarty.block.parent}]

[{if $oViewConf->getActiveTheme() === 'azure'}]
    [{oxscript include=$oViewConf->getModuleUrl('tcklarna', 'out/src/js/azure_patch.js') priority=3}]
[{/if}]
[{if $oViewConf->showCheckoutTerms() }]
    [{assign var=klarnaLawNotifUrl value=$oViewConf->getLawNotificationsLinkKco()}]
    <style>
        #legalModal iframe {
            padding-top: 30px;
            width: 100%;
            border: 0;
        }

        .modal-dialog button.close {
            position: absolute;
            right: 0;
            z-index: 1;
            font-size: 30px;
            font-weight: 700;
            line-height: 1;
            color: #000;
            text-shadow: 0 1px 0 #fff;
            filter: alpha(opacity=20);
            opacity: .2;
            background-color: #fff;
            padding: 0;
            margin: 14px;
            border: none;
        }

        .modal-dialog button.close:hover {
            opacity: .7;
        }

        label {
            font-weight: normal;
        }

        .klarna-notification {
            text-decoration: underline;
            color: #009EC0;
        }

        .klarna-notification:hover {
            text-decoration: none;
            color: #008DB0;
        }
    </style>
    <div class="klarna-modal modal fade" id="legalModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <button type="button" class="btn btn-default close pull" data-dismiss="modal">&times;</button>
                <div class="modal-body">
                    <iframe style="max-width: 660px !important; height: 500px !important;"
                            src="[{$klarnaLawNotifUrl}]" scrolling="yes"></iframe>
                </div>
            </div>
        </div>
    </div>
    [{ capture assign=lawNoticeTemplate }]
        <div class="klarna-law-notice">
            [{assign var="klarnaLawNoticeMessage" value="TCKLARNA_LAW_NOTICE"|oxmultilangassign:$oViewConf->getLawNotificationsLinkKco()}]
            [{$klarnaLawNoticeMessage}]
        </div>
    [{/capture}]
    <script>
        if (window.addEventListener) {
            window.addEventListener('load', insertKlarnaNotifications)
        } else {
            window.attachEvent('onload', insertKlarnaNotifications)
        }

        function insertKlarnaNotifications() {

            var $noticeBox = $("[{$lawNoticeTemplate|escape:javascript}]");

            // my account
            var $loginForm = $('form[name=login]');
            if ($loginForm.length > 0) {
                $noticeBox.clone()
                    .appendTo($loginForm.find('div.checkbox:last'));
            }

            var $registerForm = $('form[name=order]');
            if ($registerForm.length && $registerForm.find('input[name=cl][value=register]').length) {
                $noticeBox.clone()
                    .appendTo($registerForm.find('span.help-block').last());
            }

            $('a.klarna-notification').click(function (e) {
                e.preventDefault();
                $('#legalModal').modal('show');
            });
        }
    </script>
[{/if}]

[{if $oViewConf->getActiveTheme() === 'wave'}]
    <link rel="stylesheet" type="text/css" href="[{$oViewConf->getModuleUrl('tcklarna', 'out/src/css/tc_klarna_style_wave.css')}]"/>
[{/if}]