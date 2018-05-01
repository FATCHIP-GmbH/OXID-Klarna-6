<div class="col-sm-6">
    <div class="drop-container" id="klarnaAddressWidget">
        <div class="drop-trigger">
            <div class="klarna-label">
                <span class="glyphicon glyphicon-book pull-left" aria-hidden="true"></span>
                <span class="klarna-address-label"> [{oxmultilang ident="TCKLARNA_CHOOSE_DELIVERY_ADDRESS"}]
                            </span>
                <span class="glyphicon glyphicon-menu-down pull-right" aria-hidden="true"></span>
            </div>
        </div>
        <div class="drop-content fixed">
            <form name="address" action="[{$oViewConf->getSslSelfLink()}]" method="post" role="form">
                <div class="hidden">
                    [{$oViewConf->getHiddenSid()}]
                    <input type="hidden" name="cl" value="KlarnaExpress">
                    <input type="hidden" name="fnc" value="setKlarnaDeliveryAddress">
                    <input type="hidden" name="klarna_address_id" value="">
                </div>
                <div class="form-group">
                    <div class="btn-group">
                        <button type="button"
                                class="btn btn-default disabled js-klarna-selected-address">
                            [{oxmultilang ident="TCKLARNA_CHOOSE_DELIVERY_ADDRESS"}]</button>
                        <button type="button" class="btn btn-default dropdown-toggle"
                                data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            <span class="caret"></span>
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                            [{foreach from=$savedAddresses name=delAddrList key=oxid item=address}]
                                <li class="js-klarna-address-list-item"
                                    data-address-id="[{$oxid}]">
                                    <div class="klarna-formatted-address">[{$address}]</div>
                                </li>
                                [{if !$smarty.foreach.delAddrList.last}]
                                    <li role="separator" class="divider"></li>
                                [{/if}]
                            [{/foreach}]
                        </ul>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" id="setDeliveryAddress"
                            class="btn btn-primary">[{oxmultilang ident="TCKLARNA_USE_AS_DELIVERY_ADDRESS"}]
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

