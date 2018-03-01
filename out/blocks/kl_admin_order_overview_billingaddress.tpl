[{if $sMessage}]
    <script>
        var messagebox = '<div class="messagebox">[{$sMessage}]</div>';
    </script>
    [{oxscript add='document.getElementById("transfer").insertAdjacentHTML("afterend",messagebox);'}]

[{/if}]

[{$smarty.block.parent}]