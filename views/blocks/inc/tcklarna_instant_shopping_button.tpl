[{assign var="aInstantButton" value=$oViewConf->getInstantShoppingConfiguration()}]
[{if $aInstantButton }]
    <p><klarna-instant-shopping data-instance-id=[{$aInstantButton->getButtonInstance()}] /></p>
[{/if}]