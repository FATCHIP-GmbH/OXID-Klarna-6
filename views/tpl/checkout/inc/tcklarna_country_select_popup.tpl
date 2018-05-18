<div class="klarna-modal modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn btn-default close pull" data-dismiss="modal">&times;</button>
                <h3 class="modal-title"
                    id="myModalLabel">[{oxmultilang ident="TCKLARNA_CHOOSE_YOUR_SHIPPING_COUNTRY"}]</h3>
            </div>
            <div class="modal-body">
                <form class="form" id="select-country-form" name="select-country"
                      action="[{$oViewConf->getSslSelfLink()}]"
                      method="post">
                    <div class="hidden">
                        [{$oViewConf->getHiddenSid()}]
                        <input type="hidden" name="cl" value="KlarnaExpress">
                        <input type="hidden" name="selected-country" value="">
                    </div>
                    [{foreach from=$oView->getKlarnaModalFlagCountries() item="country" name="flagCountries" }]
                        <button type="button"
                                class="btn btn-default" value="[{$country->oxcountry__oxisoalpha2->value}]">
                            <div class="klarna-flag [{$country->oxcountry__oxisoalpha2->value|lower}]"></div>
                            <span class="country-name">[{$country->oxcountry__oxtitle->value}]</span>
                        </button>
                    [{/foreach}]
                </form>
                <div class="other-countries-select">
                    <div class="form-group">
                        <select class="form-control js-country-select" id="other-countries">
                            <option disabled selected>[{oxmultilang ident="TCKLARNA_MORE_COUNTRIES"}]</option>
                            [{foreach from=$oView->getActiveShopCountries() item="country" name="otherCountries" }]
                                <option value="[{$country->oxcountry__oxisoalpha2->value}]">[{$country->oxcountry__oxtitle->value}]</option>
                            [{/foreach}]
                        </select>
                    </div>
                </div>
                [{*<div class="country-not-on-the-list">
                    <a href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=user&invalidKlarnaCountry=1"}]">[{oxmultilang ident="TCKLARNA_MY_COUNTRY_IS_NOT_LISTED"}]</a>
                </div>*}]
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var showModal = false;
    [{if $blShowPopUp && $sKlarnaIframe}]
        showModal = true;
    [{/if}]
    if (window.addEventListener) {
        window.addEventListener('load', countryPopupHandler)
    } else {
        window.attachEvent('onload', countryPopupHandler)
    }

    function countryPopupHandler() {
        var loadedPurchaseCountry = "[{$sPurchaseCountry|upper}]";
        var $modal = $('#myModal');

        [{if $blShowPopUp && $sKlarnaIframe}]
            showModal = true;
        [{/if}]

        $('#resetCountry').on('click', 'a', function () {
            $modal.modal('show');
        });

        var $form = $('#select-country-form').closest('form');
        var $input = $form.find('input[name="selected-country"]');

        $('button', $form).click(function (e) {
            if (this.value === loadedPurchaseCountry) {
                $modal.modal('hide');
                return;
            }
            e.preventDefault();
            $input.val(this.value);
            $form.submit();
        });

        $('.js-country-select').change(function () {
            if (this.value === loadedPurchaseCountry) {
                $modal.modal('hide');
                return;
            }
            $input.val(this.value);
            $form.submit();
        });
    }


</script>