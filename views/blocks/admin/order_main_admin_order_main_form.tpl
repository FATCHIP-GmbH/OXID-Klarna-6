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
        <script>
            var messagebox = '<div class="messagebox danger">[{$sErrorMessage|escape:'quotes'}]</div>';
            document.getElementById("myedit").insertAdjacentHTML("beforebegin", messagebox);
        </script>
    [{elseif $sWarningMessage}]
        <script>
            var messagebox = '<div class="messagebox warn">[{$sWarningMessage|escape:'quotes'}]</div>';
            document.getElementById("myedit").insertAdjacentHTML("beforebegin", messagebox);
        </script>
    [{/if}]
    [{if $sMessage}]
        <script>
            var messagebox = '<div class="messagebox">[{$sMessage|escape:'quotes'}]</div>';
            document.getElementById("myedit").insertAdjacentHTML("beforebegin", messagebox);
        </script>
    [{/if}]

    [{oxscript add='document.getElementsByName("setDelSet")[0].disabled = "disabled";'}]
    [{oxscript add='document.getElementsByName("editval[oxorder__oxdelcost]")[0].disabled = "disabled";'}]
[{/if}]