[{$smarty.block.parent}]
[{assign var="aKlPromotion" value=$oViewConf->getOnSitePromotionInfo('sKlarnaBannerPromotion')}]
[{assign var="sKlarnaMessagingScript" value=$oViewConf->getOnSitePromotionInfo('sKlarnaMessagingScript')}]

[{if $aKlPromotion and $sKlarnaMessagingScript|trim}]
    <div id="banner-promotion">
        [{$aKlPromotion}]
    </div>
    <div class="clear clearfix"></div>
[{/if}]

<style>
    #banner-promotion {
        margin-top: 10px;
        text-align: center;
    }
</style>