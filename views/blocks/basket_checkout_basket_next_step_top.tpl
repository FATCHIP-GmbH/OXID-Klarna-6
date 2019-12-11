[{$smarty.block.parent}]

[{if $openAmazonLogin }]
    <script type="text/javascript">
        window.onload = clickWidgetButton;
        function clickWidgetButton(){
            var theButton = document.getElementById('OffAmazonPaymentsWidgets0');
            theButton.click();
        }
    </script>
[{/if}]

[{assign var="aKlPromotion" value=$oViewConf->getOnSitePromotionInfo('sKlarnaCreditPromotionBasket')}]
[{assign var="sKlarnaMessagingScript" value=$oViewConf->getOnSitePromotionInfo('sKlarnaMessagingScript')}]
[{if $aKlPromotion and $sKlarnaMessagingScript|trim}]
    <div class="kl-basket-rate-note">
        [{$aKlPromotion}]
    </div>
    <div class="clear clearfix"></div>
[{/if}]