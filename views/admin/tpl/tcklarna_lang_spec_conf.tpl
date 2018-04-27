[{if isset($iLang)}]
    [{assign var="editlanguage" value=$iLang}]
[{/if}]
[{assign var="lang_tag" value=$languages.$editlanguage->abbr|oxupper}]
<tr>
    <td colspan="3" class="inner-table-wrapper">
        <table>
            <tr>
                <td class="conf-label-2">[{ oxmultilang ident="TCKLARNA_SET_TAC_URI" }]</td>
                <td class="lang-input">
                    [{assign var="confVarName" value="sKlarnaTermsConditionsURI_"|cat:$lang_tag}]
                    <div class="input">
                        <input type="text" class="url-input m-lang"
                               name="confstrs[sKlarnaTermsConditionsURI_[{$lang_tag}]]"
                               value="[{$confstrs.$confVarName}]"
                               pattern="^(https://)?([a-zA-Z0-9]([a-zA-ZäöüÄÖÜ0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}.*" required>
                    </div>
                </td>
                <td>
                    <span class="kl-tooltip"
                          title="[{oxmultilang ident="TCKLARNA_TERMS_AND_CONDITIONS_URL_TOOLTIP"}]">
                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                    </span>
                </td>
            </tr>
            <tr>
                <td class="conf-label-2">[{ oxmultilang ident="TCKLARNA_SET_CANCEL_URI" }]</td>
                <td class="lang-input">
                    [{assign var="confVarName" value="sKlarnaCancellationRightsURI_"|cat:$lang_tag}]
                    <div class="input">
                        <input type="text" class="url-input m-lang"
                               name="confstrs[sKlarnaCancellationRightsURI_[{$lang_tag}]]"
                               value="[{$confstrs.$confVarName}]"
                               pattern="^(https://)?([a-zA-Z0-9]([a-zA-ZäöüÄÖÜ0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}.*" required>
                    </div>
                </td>
                <td>
                    <span class="kl-tooltip"
                          title="[{oxmultilang ident="TCKLARNA_CANCELLATION_RIGHTS_URL_TOOLTIP"}]">
                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                    </span>
                </td>
            </tr>
            <tr>
                <td class="conf-label-2">[{ oxmultilang ident="TCKLARNA_SHIPPING_DETAILS" }]</td>
                <td class="lang-input">
                    [{assign var="confVarName" value="sKlarnaShippingDetails_"|cat:$lang_tag}]
                    <div class="input">
                        <input type="text" class="m-lang"
                               name="confstrs[sKlarnaShippingDetails_[{$lang_tag}]]"
                               value="[{$confstrs.$confVarName}]">
                    </div>
                </td>
                <td>
                    <span class="kl-tooltip"
                          title="[{oxmultilang ident="TCKLARNA_SHIPPING_DETAILS_TOOLTIP"}]">
                        <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                    </span>
                </td>
            </tr>
        </table>
    </td>
</tr>