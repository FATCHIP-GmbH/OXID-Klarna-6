[{capture append="oxidBlock_content"}]
    <link rel="stylesheet" type="text/css" href="[{$oViewConf->getModuleUrl('klarna', 'out/css/bootstrap.min.css')}]"/>
    [{if !$confError}]
        [{oxstyle include=$oViewConf->getModuleUrl('klarna', 'out/css/kl_klarna_style.css')}]

        [{include file='kl_klarna_country_select_popup.tpl'}]
        [{if $sKlarnaIframe}]
            [{assign var="savedAddresses" value=$oView->getFormattedUserAddresses()}]
            <div class="container klarna-outside-forms">
                <div class="row kco-style">
                    [{if !$oViewConf->isUserLoggedIn()}]
                        [{include file='kl_klarna_checkout_login_box.tpl'}]
                    [{else}]
                        [{if $savedAddresses && $shippingAddressAllowed}]
                            [{include file='kl_klarna_checkout_address_box.tpl'}]
                        [{/if}]
                    [{/if}]
                    [{include file='kl_klarna_checkout_voucher_box.tpl'}]
                </div>
                [{if $blShowCountryReset }]
                    <div class="row kco-style">
                        <p id="resetCountry">[{"KL_RESET_COUNTRY"|oxmultilangassign:$sCountryName}] (<a class=""
                                                                                                        href="#">[{"KL_CHANGE_COUNTRY"|oxmultilangassign}]</a>)
                        </p>
                    </div>
                [{/if}]
            </div>
            <div class="klarna-iframe-container">
                [{$sKlarnaIframe}]
                [{*Add oxid js code. Once the snippet is injected we can use window._klarnaCheckout*}]
                [{oxscript include=$oViewConf->getModuleUrl('klarna','out/js/klarna_checkout_handler.js') priority=10}]
            </div>
        [{else}]
            <div class="kco-placeholder"></div>
            [{oxscript include=$oViewConf->getModuleUrl('klarna','out/js/klarna_checkout_handler.js') priority=10}]
            [{oxscript add="$('#myModal').modal('show');"}]
        [{/if}]

    [{/if}]
[{/capture}]

[{include file="layout/page.tpl"}]
