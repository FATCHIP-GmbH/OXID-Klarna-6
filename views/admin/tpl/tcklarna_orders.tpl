[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]
<style>
    .table {
        width: 100%;
        max-width: 100%;
        margin-bottom: 20px;
    }

    .table > thead > tr > th,
    .table > tbody > tr > th,
    .table > tfoot > tr > th,
    .table > thead > tr > td,
    .table > tbody > tr > td,
    .table > tfoot > tr > td {
        padding: 8px;
        line-height: 1.42857143;
        vertical-align: top;
        border-top: 1px solid #ddd;
    }

    .table > thead > tr > th {
        vertical-align: bottom;
        border-bottom: 2px solid #ddd;
    }

    .table > caption + thead > tr:first-child > th,
    .table > colgroup + thead > tr:first-child > th,
    .table > thead:first-child > tr:first-child > th,
    .table > caption + thead > tr:first-child > td,
    .table > colgroup + thead > tr:first-child > td,
    .table > thead:first-child > tr:first-child > td {
        border-top: 0;
    }

    .table > tbody + tbody {
        border-top: 2px solid #ddd;
    }

    .table .table {
        background-color: #fff;
    }

    .table-condensed > thead > tr > th,
    .table-condensed > tbody > tr > th,
    .table-condensed > tfoot > tr > th,
    .table-condensed > thead > tr > td,
    .table-condensed > tbody > tr > td,
    .table-condensed > tfoot > tr > td {
        padding: 5px;
    }

    .table-bordered {
        border: 1px solid #ddd;
    }

    .table-bordered > thead > tr > th,
    .table-bordered > tbody > tr > th,
    .table-bordered > tfoot > tr > th,
    .table-bordered > thead > tr > td,
    .table-bordered > tbody > tr > td,
    .table-bordered > tfoot > tr > td {
        border: 1px solid #ddd;
    }

    .table-bordered > thead > tr > th,
    .table-bordered > thead > tr > td {
        border-bottom-width: 2px;
    }

    .table-striped > tbody > tr:nth-of-type(odd) {
        background-color: #f9f9f9;
    }

    table col[class*="col-"] {
        position: static;
        display: table-column;
        float: none;
    }

    table td[class*="col-"],
    table th[class*="col-"] {
        position: static;
        display: table-cell;
        float: none;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }

    .alert .alert-link {
        font-weight: bold;
    }

    .alert-link {
        text-decoration: underline;
    }

    .alert-link:hover {
        text-decoration: none;
    }

    .alert-success {
        background-color: #dff0d8;
        border-color: #d6e9c6;
        color: #3c763d;
    }

    .alert-success .alert-link {
        color: #2b542c;
    }

    .alert-warning {
        background-color: #fcf8e3;
        border-color: #faebcc;
        color: #8a6d3b;
    }

    .alert-warning .alert-link {
        color: #66512c;
    }

    .portal-button {
        margin: 5px 0 0 0;
    }
</style>
[{ if $readonly}]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]

<form name="transfer" id="transfer" action="[{ $oViewConf->getSelfLink() }]" method="post">
    [{ $oViewConf->getHiddenSid() }]
    <input type="hidden" name="oxid" value="[{ $oxid }]">
    <input type="hidden" name="cl" value="KlarnaOrders">
</form>
[{if $wrongCredentials}]
    [{assign var=alertMessage value=$wrongCredentials }]
    [{assign var=alertClass value="alert-warning"}]
[{/if}]
[{if $oOrder }]
    [{assign var=klarnaLink value=$oView->getKlarnaPortalLink()}]
    [{if $wrongCredentials}]
        [{assign var=alertMessage value=$wrongCredentials }]
        [{assign var=alertClass value="alert-warning"}]
    [{elseif $cancelled}]
        [{assign var=alertMessage_1 value="KLARNA_ORDER_IS_CANCELLED"|oxmultilangassign }]
        [{assign var=alertMessage_2 value="KLARNA_SEE_ORDER_IN_PORTAL"|oxmultilangassign:$klarnaLink }]
        [{assign var=alertMessage value=$alertMessage_1|cat:$alertMessage_2 }]
        [{assign var=alertClass value="alert-warning"}]
    [{elseif $unauthorizedRequest }]
        [{assign var=alertMessage value=$unauthorizedRequest }]
        [{assign var=alertClass value="alert-warning"}]
    [{else}]
        [{if $inSync}]
            [{assign var=alertMessage value="KLARNA_SEE_ORDER_IN_PORTAL"|oxmultilangassign:$klarnaLink }]
            [{assign var=alertClass value="alert-success"}]
        [{else}]
            [{assign var=alertMessage_1 value="KLARNA_ORDER_NOT_IN_SYNC"|oxmultilangassign }]
            [{assign var=alertMessage_2 value="KLARNA_SEE_ORDER_IN_PORTAL"|oxmultilangassign:$klarnaLink }]
            [{assign var=alertMessage value=$alertMessage_1|cat:$alertMessage_2 }]
            [{assign var=alertClass value="alert-warning"}]
        [{/if}]
    [{/if}]
    <div class="alert [{ $alertClass }]">
        <span style='float: left'>[{$alertMessage}] [{if !$wrongCredentials}](Merchant Id: [{$sMid}])[{/if}]</span>
        <span style='float: right'>
            [{if $oOrder->oxorder__tcklarna_orderid->value }]
                [{assign var=tcklarna_orderid value=$oOrder->oxorder__tcklarna_orderid->value}]
            [{else}]
                [{assign var=tcklarna_orderid value=" - "}]
            [{/if}]
            [{if $sKlarnaRef eq ''}]
                [{assign var=sKlarnaRef value=" - "}]
            [{/if}]
            <strong>Klarna order ID:</strong> <i>[{$tcklarna_orderid}]</i> <strong>Klarna reference:</strong> <i>[{$sKlarnaRef}]</i>
        </span>
        <div style='clear:both'></div>
    </div>
    [{if $aCaptures}]
        <div>
            <h2>[{oxmultilang ident="KLARNA_CAPTURES"}]</h2>
            <table class="table table-condensed table-striped">
                <thead>
                <tr>
                    <th>[{oxmultilang ident="KLARNA_CAPTURE_ID"}]</th>
                    <th>[{oxmultilang ident="KLARNA_CAPTURE_REFERENCE"}]</th>
                    <th>[{oxmultilang ident="KLARNA_CAPTURE_AMOUNT"}]</th>
                    <th>[{oxmultilang ident="KLARNA_CAPTURE_DATE"}]</th>
                </tr>
                </thead>
                [{foreach from=$aCaptures item="capture"}]
                    <tr>
                        <td>
                            [{$capture.capture_id}]
                        </td>
                        <td>
                            [{$capture.klarna_reference}]
                        </td>
                        <td>
                            [{$oView->formatPrice($capture.captured_amount)}]
                        </td>
                        <td>
                            [{$capture.captured_at}]
                        </td>
                    </tr>
                [{/foreach}]
            </table>
        </div>
        [{if $aRefunds }]
            <div>
                <h2>[{oxmultilang ident="KLARNA_REFUNDS"}]</h2>
                <table class="table table-condensed table-striped">
                    <thead>
                    <tr>
                        <th>[{oxmultilang ident="KLARNA_REFUND_AMOUNT"}]</th>
                        <th>[{oxmultilang ident="KLARNA_REFUND_DESCRIPTION"}]</th>
                        <th>[{oxmultilang ident="KLARNA_REFUND_DATE"}]</th>
                    </tr>
                    </thead>
                    [{foreach from=$aRefunds item="refund"}]
                        <tr>
                            <td>
                                [{$oView->formatPrice($refund.refunded_amount)}]
                            </td>
                            <td>
                                [{$refund.description}]
                            </td>
                            <td>
                                [{$refund.refunded_at}]
                            </td>
                        </tr>
                    [{/foreach}]
                </table>
            </div>
        [{/if}]
    [{elseif !$cancelled && !$wrongCredentials && !$unauthorizedRequest && $inSync }]
        <div class="portal-button">
            <form name="capture" id="capture" action="[{ $oViewConf->getSelfLink() }]" method="post">
                [{ $oViewConf->getHiddenSid() }]
                <input type="hidden" name="cl" value="KlarnaOrders">
                <input type="hidden" name="oxid" value="[{ $oxid }]">
                <input type="hidden" name="fnc" value="captureFullOrder">

                <input type="submit" value="[{ oxmultilang ident="KLARNA_CAPTURE_FULL_ORDER" }]" class="alert-success">
            </form>
        </div>
        <div class="portal-button">
            <form name="cancel" id="cancel" action="[{ $oViewConf->getSelfLink() }]" method="post">
                [{ $oViewConf->getHiddenSid() }]
                <input type="hidden" name="cl" value="KlarnaOrders">
                <input type="hidden" name="oxid" value="[{ $oxid }]">
                <input type="hidden" name="fnc" value="cancelOrder">

                <input type="submit" value="[{ oxmultilang ident="KLARNA_CANCEL_ORDER" }]" class="alert-warning">
            </form>
        </div>
    [{/if}]
[{else}]
    [{if $wrongCredentials}]
        <div class="alert [{ $alertClass }]">
            [{$alertMessage}]
        </div>
    [{else}]
        <div class="messagebox">[{$sMessage}]</div>
    [{/if}]
[{/if}]

[{include file="bottomitem.tpl"}]
