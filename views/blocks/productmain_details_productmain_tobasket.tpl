[{$smarty.block.parent}]
[{if !$oDetailsProduct->isNotBuyable() && $oViewConf->isKlarnaPaymentsEnabled() && $oViewConf->displayExpressButton()}]
    <div id="klarna_express_button" style="display: none"></div>

    <script>
        var tcKlarnaKebTheme = "[{$kebtheme}]";
        var tcKlarnaKebShape = "[{$kebshape}]";
    </script>
    [{oxscript include="https://x.klarnacdn.net/kp/lib/v1/api.js"}]
    <script>
        window.klarnaAsyncCallback = function () {
            window.Klarna.Payments.Buttons.init({
                client_id: '[{$oViewConf->getKEBClientId()}]',
            }).load(
                {
                    container: '#klarna_express_button',
                    theme: tcKlarnaKebTheme,
                    shape: tcKlarnaKebShape,
                    locale: '[{$oViewConf->getLocale()}]',
                    on_click: (authorize) => {

                        var form = $('#toBasket').parents('form');
                        var inputElement = form.find('input[type="hidden"][name="fnc"][value="tobasket"]');
                        inputElement.remove();

                        $.ajax({
                            type: 'POST',
                            data: form.serialize(),
                            dataType: 'json',
                            url: '?cl=oxwarticledetails&fnc=tobasketKEB',
                            success: function (response) {
                                try {
                                    Klarna.Payments.init({
                                         client_token: response.clientToken
                                     });
                                } catch (e) {
                                    console.error(e);
                                }

                                authorize({ auto_finalize: false, collect_shipping_address: true }, response.klarnaSessionData, (result) => {
                                    console.log(result);
                                    // Here you will receive customer data and client_token necessary to resume the authorization in your checkout.
                                })
                            }
                        })


                    },
                },
                function load_callback(loadResult) {
                    $('#klarna_express_button').show();
                }
            )
        }

    </script>
[{/if}]

[{assign var="aKlPromotion" value=$oViewConf->getOnSitePromotionInfo('sKlarnaCreditPromotionProduct', $oDetailsProduct)}]
[{assign var="sKlarnaMessagingScript" value=$oViewConf->getOnSitePromotionInfo('sKlarnaMessagingScript')}]
[{if $aKlPromotion and $sKlarnaMessagingScript|trim}]
    <div id="credit_promo">
        [{$aKlPromotion}]
    </div>
    <div class="clear clearfix"></div>
[{/if}]