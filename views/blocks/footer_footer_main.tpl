[{$smarty.block.parent}]

[{oxscript include=$oViewConf->getModuleUrl('tcklarna','out/src/js/tcklarna_scripts.js') priority=10 }]

[{assign var="sKlBanner" value=$oViewConf->getKlarnaHomepageBanner() }]

[{*  *}]
[{if ($sKlBanner && $oView->getClassName() === 'start') }]
    [{if $oViewConf->getActiveTheme() == 'azure' }]
        <script>
            var re = /src="(.*)"/g;
            var strBannerSrc = '[{$sKlBanner|escape:javascript}]';
            var url = re.exec(strBannerSrc)[1];
            var separator = document.createElement('div');
            separator.style.height = '20px';
            document.querySelector('#content div').after(separator);

            var bannerSrc = document.createElement('script');
            bannerSrc.setAttribute('src', url);
            bannerSrc.async = true;

            var wrapper = document.createElement('div');
            wrapper.appendChild(bannerSrc);
            document.querySelector('#content div').after(wrapper);
        </script>
    [{else}]
        [{capture assign=klContentMainBanner }]
            [{$sKlBanner }]
            <div style="height:20px;"></div>
        [{/capture}]
        [{assign var=klContentMainBanner value=$klContentMainBanner|escape:javascript }]
        [{oxscript add='$(\'#content div:first\').after(\''|cat:$klContentMainBanner|cat:'\');' }]
    [{/if}]
[{/if}]

[{assign var="aKlFooter" value=$oViewConf->getKlarnaFooterContent()}]
[{if $aKlFooter}]

    [{capture assign=klFooterContent }]
        [{if $oViewConf->getActiveTheme() == 'azure' }]
            <li class="klarna-logo">
                <style>
                    .kl-logo {margin-top: 30px;padding: 4px 20px;}
                    .kl-logo img { max-width: 100%;}
                </style>

                <div class="kl-logo">
                    <div class="kl-logo-inner">
                        <img src="[{$aKlFooter.url}]">
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
                    <div class="[{if ($aKlFooter.class === 'logoBlack' || $aKlFooter.class === 'logoWhite') }]kl-logo-inner[{/if}]">
                        <img src="[{$aKlFooter.url}]">
                    </div>
                </div>
            </section>
        [{/if}]
    [{/capture}]

    <script type="text/javascript">
        function embedKlarnaLogo(content) {
            var theme = '[{$oViewConf->getActiveTheme()}]';
            var $content = $(content)
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
    </script>
    [{assign var=klFooterContent value=$klFooterContent|escape:javascript }]
    [{oxscript add="embedKlarnaLogo('$klFooterContent');"}]
[{/if}]



