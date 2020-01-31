[{$smarty.block.parent}]

[{assign var="aKlPromotion" value=$oViewConf->getOnSitePromotionInfo('sKlarnaCreditPromotionBasket')}]
[{assign var="sKlarnaMessagingScript" value=$oViewConf->getOnSitePromotionInfo('sKlarnaMessagingScript')}]
[{if $aKlPromotion and $sKlarnaMessagingScript|trim}]
    <div class="clear clearfix"></div>

    <div class="kl-basket-rate-note pull-right" style="margin-bottom: 20px;">
        [{$aKlPromotion}]
    </div>
[{/if}]