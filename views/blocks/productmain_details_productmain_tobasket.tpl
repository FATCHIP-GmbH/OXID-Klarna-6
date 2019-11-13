[{$smarty.block.parent}]
[{if !$oDetailsProduct->isNotBuyable() && $oViewConf->isKlarnaCheckoutEnabled() && $oViewConf->addBuyNow()}]
    <div>
        <a class="btn btn-primary largeButton submitButton klarna-express-button [{if !$blCanBuy}]disabled[{/if}]" href="#">
            [{oxmultilang ident="TCKLARNA_BUY_NOW"}]
        </a>
    </div>
    <div class="clear clearfix"></div>
    [{oxscript add='$(".klarna-express-button").KlarnaProceedAction( {sAction: "actionKlarnaExpressCheckoutFromDetailsPage"} );'}]
[{/if}]

[{include file="tcklarna_instant_shopping_button.tpl"}]

[{assign var="aKlPromotion" value=$oViewConf->getOnSitePromotionInfo('sKlarnaCreditPromotionProduct', $oDetailsProduct)}]
[{assign var="sKlarnaMessagingScript" value=$oViewConf->getOnSitePromotionInfo('sKlarnaMessagingScript')}]
[{if $aKlPromotion and $sKlarnaMessagingScript|trim}]
    <div>
        [{$aKlPromotion}]
    </div>
    <div class="clear clearfix"></div>
[{/if}]

<style>
    .klarna-express-button {
        margin-bottom: 5px;
    }
</style>