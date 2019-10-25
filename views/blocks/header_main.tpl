[{assign var="aKlPromotion" value=$oViewConf->getOnSitePromotionInfo('sKlarnaStripPromotion')}]

[{if $aKlPromotion && $oView->getClassName() === 'start'}]
    <div>
        [{$aKlPromotion}]
    </div>
    <div class="clear clearfix"></div>
[{/if}]

[{$smarty.block.parent}]