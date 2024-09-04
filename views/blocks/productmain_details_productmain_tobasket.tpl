[{$smarty.block.parent}]
[{if !$oDetailsProduct->isNotBuyable() && $oViewConf->isKlarnaPaymentsEnabled() && $oViewConf->displayExpressButton()}]
    <div id="klarna_express_button" style="display: none"></div>

    <script>
        var userExists = [{$oView->isUserLoggedIn()}];
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
                            success: function (keborderpayload) {
                                authorize({ auto_finalize: false, collect_shipping_address: true }, keborderpayload, (result) => {
                                    tcKlarnaRedirectToCheckout(result);
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

        function tcKlarnaRedirectToCheckout(kebauthresponse) {
            if (!kebauthresponse || kebauthresponse.approved === false) {
                window.location.reload();
            } else {
                var form = document.createElement('form');
                var token = $('input[name="stoken"]').val();
                form.method = 'POST';
                form.action = '?cl=order&redirected=1&stoken='+token;

                var kebauthrespField = document.createElement('input');
                kebauthrespField.type = 'hidden';
                kebauthrespField.name = 'kebauthresponse';
                kebauthrespField.value = JSON.stringify(kebauthresponse);
                form.appendChild(kebauthrespField);

                document.body.appendChild(form);
                form.submit();
            }
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

<br>