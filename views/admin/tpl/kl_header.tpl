<div class="klarna-header">
    <div class="w7">
        <h1>[{ $title }]</h1>
            [{if !$tabName }] [{ assign var="tabName" value=$title }] [{/if}]
        <p>[{ "KL_GENERAL_SETTINGS_ADMIN_DESC"|oxmultilangassign|sprintf:$tabName:$oView->getManualDownloadLink() }]
    </div>
    <div class="w5">
        <div class="klarna-logo">
        </div>
    </div>
</div>