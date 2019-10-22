[{$smarty.block.parent}]
[{assign var="aKlPromotion" value=$oViewConf->getKlarnaConfVar('sKlarnaBannerPromotion')}]

[{if $aKlPromotion}]
    <div style="margin-top: 10px">
        [{$aKlPromotion}]
    </div>
    <div class="clear clearfix"></div>
[{/if}]