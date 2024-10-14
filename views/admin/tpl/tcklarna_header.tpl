<div class="klarna-header">
    <div class="w7">
        <h1>[{$title}]</h1>
        <p>[{$desc|sprintf:$oView->getManualDownloadLink()}]
    </div>
    <div class="w5">
        <div class="klarna-logo">
        </div>
    </div>
</div>
<div>
    [{if $Errors|is_array && $Errors.default|is_array && !empty($Errors.default)}]
        [{foreach from=$Errors.default item=oEr key=key}]
            <div class="messagebox danger" style="display:block;">[{$oEr->getOxMessage()}]</div>
        [{/foreach}]
    [{/if}]
    [{if $Errors.popup|is_array && !empty($Errors.popup)}]
        [{include file="message/errors_modal.tpl"}]
    [{/if}]
</div>