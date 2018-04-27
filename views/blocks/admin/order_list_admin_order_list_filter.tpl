[{$smarty.block.parent}]
[{if $isKlarnaOrder}]
    <style>
        div.messagebox.warn {
            color: #8a6d3b;
            background-color: #fcf8e3;
            border-color: #faebcc;
        }

        div.messagebox.danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
    [{if $sErrorMessage}]
        <script type="text/javascript">
            var messagebox = '<div class="messagebox danger">[{$sErrorMessage|escape:'quotes'}]</div>';
            document.getElementById("transfer").insertAdjacentHTML("afterend", messagebox);
        </script>
    [{elseif $sWarningMessage}]
        <script type="text/javascript">
            var messagebox = '<div class="messagebox warn">[{$sWarningMessage|escape:'quotes'}]</div>';
            document.getElementById("transfer").insertAdjacentHTML("afterend", messagebox);
        </script>
    [{/if}]
    [{if $sMessage}]
        <script type="text/javascript">
            var messagebox = '<div class="messagebox">[{$sMessage|escape:'quotes'}]</div>';
            document.getElementById("transfer").insertAdjacentHTML("afterend", messagebox);
        </script>
    [{/if}]
[{/if}]