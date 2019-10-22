[{assign var="aKlPromotion" value=$oViewConf->getKlarnaConfVar('sKlarnaStripPromotion')}]

[{if $aKlPromotion}]
    <div>
        [{$aKlPromotion}]
    </div>
    <div class="clear clearfix"></div>
[{/if}]

[{$smarty.block.parent}]