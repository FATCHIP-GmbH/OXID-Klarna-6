[{$smarty.block.parent}]

[{assign var="aKlPromotion" value=$oViewConf->getOnSitePromotionInfo('sKlarnaCreditPromotionBasket')}]
[{assign var="sKlarnaMessagingScript" value=$oViewConf->getOnSitePromotionInfo('sKlarnaMessagingScript')}]
[{if $aKlPromotion and $sKlarnaMessagingScript|trim}]
    <div class="clear clearfix"></div>

    <div class="col-12">
        <div class="kl-basket-rate-note pull-right float-right"
                [{if $oViewConf->isActiveThemeFlow()}]style="margin-bottom: 20px;"[{/if}]
        >
            [{$aKlPromotion}]
        </div>
    </div>
[{/if}]