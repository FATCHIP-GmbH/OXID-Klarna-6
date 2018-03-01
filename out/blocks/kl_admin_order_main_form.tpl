[{if $sMessage}]
    <script>
        var messagebox = '<div class="messagebox">[{$sMessage}]</div>';
    </script>
    [{oxscript add='document.getElementById("myedit").insertAdjacentHTML("beforebegin",messagebox);'}]

[{/if}]

[{$smarty.block.parent}]

[{if $isKlarnaOrder}]
    [{oxscript add='document.getElementsByName("setDelSet")[0].disabled = "disabled";'}]
    [{oxscript add='document.getElementsByName("editval[oxorder__oxdelcost]")[0].disabled = "disabled";'}]
[{/if}]