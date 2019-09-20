[{capture append="oxidBlock_content"}]
    [{if !$confError}]
        [{oxstyle include=$oViewConf->getModuleUrl('tcklarna', 'out/src/css/tcklarna_style.css')}]

        [{include file='tcklarna_country_select_popup.tpl'}]
        [{if $sKlarnaIframe}]
            [{assign var="savedAddresses" value=$oView->getFormattedUserAddresses()}]
            <div class="container klarna-outside-forms">
                <div class="row kco-style">
                    [{if !$oViewConf->isUserLoggedIn()}]
                        [{include file='tcklarna_checkout_login_box.tpl'}]
                    [{else}]
                        [{if $savedAddresses && $shippingAddressAllowed}]
                            [{include file='tcklarna_checkout_address_box.tpl'}]
                        [{/if}]
                    [{/if}]
                    [{include file='tcklarna_checkout_voucher_box.tpl'}]
                </div>
                [{if $blShowCountryReset }]
                    <div class="row kco-style">
                        <div class="col-md-6">
                            <p id="resetCountry" style="margin-left: 0">
                                [{oxmultilang ident="TCKLARNA_CHOOSE_YOUR_NOT_SUPPORTED_COUNTRY"}]
                            </p>
                        </div>
                        [{if $oView->getNonKlarnaCountries()|count > 0}]
                            <div class="col-md-6">
                                <select class="form-control js-country-select" id="other-countries">
                                    <option disabled selected>[{oxmultilang ident="TCKLARNA_MORE_COUNTRIES"}]</option>
                                    [{foreach from=$oView->getNonKlarnaCountries() item="country" name="otherCountries" }]
                                        <option value="[{$country->oxcountry__oxisoalpha2->value}]">[{$country->oxcountry__oxtitle->value}]</option>
                                    [{/foreach}]
                                </select>
                            </div>
                        [{/if}]
                    </div>
                [{/if}]
            </div>
            <div class="klarna-iframe-container">
                [{$sKlarnaIframe}]
                [{*Add oxid js code. Once the snippet is injected we can use window._klarnaCheckout*}]
                [{oxscript include=$oViewConf->getModuleUrl('tcklarna','out/src/js/tcklarna_checkout_handler.js') priority=10}]
            </div>
        [{else}]
            <div class="kco-placeholder"></div>
            [{oxscript include=$oViewConf->getModuleUrl('tcklarna','out/src/js/tcklarna_checkout_handler.js') priority=10}]
            [{oxscript add="$('#myModal').modal('show');"}]
        [{/if}]

    [{/if}]
[{/capture}]

[{include file="layout/page.tpl"}]