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

[{assign var="aKlPromotion" value=$oViewConf->getKlarnaConfVar('sKlarnaCreditPromotionBasket')}]
[{if $aKlPromotion}]
    <div>
        [{$aKlPromotion}]
    </div>
    <div class="clear clearfix"></div>
[{/if}]