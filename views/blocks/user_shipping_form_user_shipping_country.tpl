[{if $oViewConf->isCheckoutNonKlarnaCountry() && $oView->getIsOrderStep() }]
    <div class="form-group[{if $aErrors.oxaddress__oxcountryid}] oxInValid[{/if}]">
        <label class="control-label col-lg-3[{if $oView->isFieldRequired(oxaddress__oxcountryid)}] req[{/if}]"
               for="delCountrySelect">[{oxmultilang ident="COUNTRY"}]</label>
        <div class="col-lg-9">
            <select class="form-control[{if $oView->isFieldRequired(oxaddress__oxcountryid)}] js-oxValidate js-oxValidate_notEmpty[{/if}] selectpicker"
                    id="delCountrySelect"
                    name="deladr[oxaddress__oxcountryid]"[{if $oView->isFieldRequired(oxaddress__oxcountryid)}] required=""[{/if}]>
                <option value="">-</option>
                [{assign var="blCountrySelected" value=false}]
                [{foreach from=$oViewConf->getCountryList(true) item=country key=country_id}]
                    [{assign var="sCountrySelect" value=""}]
                    [{if !$blCountrySelected}]
                        [{if (isset($deladr.oxaddress__oxcountryid) && $deladr.oxaddress__oxcountryid == $country->oxcountry__oxid->value) ||
                        (!isset($deladr.oxaddress__oxcountryid) && ($delivadr->oxaddress__oxcountry->value == $country->oxcountry__oxtitle->value or
                        $delivadr->oxaddress__oxcountry->value == $country->oxcountry__oxid->value or
                        $delivadr->oxaddress__oxcountryid->value == $country->oxcountry__oxid->value))}]
                            [{assign var="blCountrySelected" value=true}]
                            [{assign var="sCountrySelect" value="selected"}]
                        [{/if}]
                    [{/if}]
                    <option value="[{$country->oxcountry__oxid->value}]" [{$sCountrySelect}]>[{$country->oxcountry__oxtitle->value}]</option>
                [{/foreach}]
            </select>
            [{if $oView->isFieldRequired(oxaddress__oxcountryid)}]
                [{include file="message/inputvalidation.tpl" aErrors=$aErrors.oxaddress__oxcountryid}]
                <div class="help-block"></div>
            [{/if}]
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-lg-3"
               for="[{$countrySelectId}]">[{oxmultilang ident="DD_USER_SHIPPING_LABEL_STATE"}]</label>
        <div class="col-lg-9">
            [{include file="form/fieldset/state.tpl"
            countrySelectId="delCountrySelect"
            stateSelectName="deladr[oxaddress__oxstateid]"
            selectedStateIdPrim=$deladr.oxaddress__oxstateid
            selectedStateId=$delivadr->oxaddress__oxstateid->value
            class="form-control selectpicker"}]
        </div>
    </div>
[{else}]
    [{$smarty.block.parent}]
[{/if}]