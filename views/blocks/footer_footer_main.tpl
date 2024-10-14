[{$smarty.block.parent}]

[{assign var="aKlFooter" value=$oViewConf->getKlarnaFooterContent()}]
[{if $aKlFooter.script}]
    [{$aKlFooter.script}]
[{/if}]
[{oxscript include=$oViewConf->getModuleUrl('tcklarna','out/src/js/tcklarna_scripts.js') priority=10}]

[{if $aKlFooter}]

    [{capture assign=klFooterContent}]
    [{if $aKlFooter.url}]
        [{if $oViewConf->getActiveTheme() == 'azure'}]
            <li class="klarna-logo">
                <style>
                    .kl-logo {margin-top: 30px;padding: 4px 20px;}
                    .kl-logo img { max-width: 100%;}
                </style>

                <div class="kl-logo">
                    <div class="kl-logo-inner">
                        <img width="135" height="75" src="[{$aKlFooter.url}]">
                    </div>
                </div>
            </li>
        [{else}]
            <section class="klarna-logo">
                <style>
                    .kl-logo { margin-top: 30px; }
                    .kl-logo-inner { width: 80%; }
                    .kl-logo img { max-width: 100%;}
                </style>

                <div class="kl-logo">
                    <div class="[{if ($aKlFooter.class === 'logoFooter' || $aKlFooter.class === 'logoBlack' || $aKlFooter.class === 'logoWhite')}]kl-logo-inner[{/if}]">
                        <img [{if ($aKlFooter.class === 'logoFooter')}]width="135" height="75"[{/if}] src="[{$aKlFooter.url}]">
                    </div>
                </div>
            </section>
        [{/if}]
    [{/if}]
    [{/capture}]

    <script type="text/javascript">
        function embedKlarnaLogo(content) {
            var theme = '[{$oViewConf->getActiveTheme()}]';
            var $content = $(content)

            if($content.length < 0) {
                if(theme === 'flow'){
                    $('.footer-right-part div:first').append($content);
                }
                if(theme === 'wave'){
                    $('.footer-box-newsletter').append($content);
                }
                if(theme === 'azure'){
                    $('#footerCategories .list.categories').append($content);
                }

                // get logo in natural size
                var $img = $content.find('img');
                var parsedUrl = $img.attr('src').split('width=');
                if(parsedUrl.length > 1) {
                    var prevStyle = getComputedStyle($content.prev().children().first()[0]);
                    $img.attr('src', parsedUrl[0] + 'width=' + parseInt(prevStyle.width));
                }
            }
        }
    </script>
    [{assign var=klFooterContent value=$klFooterContent|escape:javascript}]
    [{oxscript add="embedKlarnaLogo('$klFooterContent');"}]
[{/if}]


