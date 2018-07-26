[{$smarty.block.parent}]

[{if !$oDetailsProduct->isNotBuyable() && $oViewConf->isKlarnaCheckoutEnabled() && $oViewConf->addBuyNow()}]
        <div>
            [{if !$blCanBuy}]
                    <a class="btn btn-primary largeButton submitButton klarna-express-button disabled" href="#">
                        [{oxmultilang ident="TCKLARNA_BUY_NOW"}]
                    </a>
            [{else}]
                    <a class="btn btn-primary largeButton submitButton klarna-express-button">
                        [{oxmultilang ident="TCKLARNA_BUY_NOW"}]
                    </a>
            [{/if}]
        </div>
    <div class="clear clearfix"></div>
    [{oxscript add='$(".klarna-express-button").KlarnaProceedAction( {sAction: "actionKlarnaExpressCheckoutFromDetailsPage"} );'}]
[{/if}]