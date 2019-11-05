[{assign var="oKlarnaButton" value=$oViewConf->getInstantShoppingButton()}]
[{if $oKlarnaButton}]
    <p><klarna-instant-shopping [{if $blCanBuy === false}]style="pointer-events: none;"[{/if}]></p>
    [{if $smarty.get.oxwparent === 'details'}]
        [{* check if ajax request*}]
        [{capture assign="updateJs"}]
            klButtonManager.updateInstances([{$oKlarnaButton->getConfig($oDetailsProduct)|@json_encode}]);
        [{/capture}]
        [{oxscript add=$updateJs}]
    [{/if}]
[{/if}]