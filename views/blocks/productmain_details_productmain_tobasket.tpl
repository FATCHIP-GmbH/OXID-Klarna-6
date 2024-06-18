[{$smarty.block.parent}]
[{if !$oDetailsProduct->isNotBuyable() && $oViewConf->isKlarnaCheckoutEnabled() && $oViewConf->displayExpressButton()}]
    <div>
        <a class="btn btn-primary largeButton submitButton klarna-express-button [{if !$blCanBuy}]disabled[{/if}]" href="#">
            [{oxmultilang ident="TCKLARNA_BUY_NOW"}]
        </a>
    </div>
    [{oxscript add='$(".klarna-express-button").KlarnaProceedAction( {sAction: "actionKlarnaExpressCheckoutFromDetailsPage"} );'}]
[{/if}]

[{assign var="aKlPromotion" value=$oViewConf->getOnSitePromotionInfo('sKlarnaCreditPromotionProduct', $oDetailsProduct)}]
[{assign var="sKlarnaMessagingScript" value=$oViewConf->getOnSitePromotionInfo('sKlarnaMessagingScript')}]
[{if $aKlPromotion and $sKlarnaMessagingScript|trim}]
    <div id="credit_promo">
        [{$aKlPromotion}]
    </div>
    <div class="clear clearfix"></div>
[{/if}]

<style>
[{*    TODO: get confs for KEB *}]
    .klarna-express-button {
        margin-bottom: 5px;
    }

    body.cl-details .tobasket .tobasketFunction{
        display:inline-block;
    }
</style>