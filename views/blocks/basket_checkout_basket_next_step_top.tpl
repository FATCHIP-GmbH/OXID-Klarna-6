[{$smarty.block.parent}]

[{if $openAmazonLogin }]
    <script type="text/javascript">
        window.onload = clickWidgetButton;
        function clickWidgetButton(){
            var theButton = document.getElementById('OffAmazonPaymentsWidgets0');
            theButton.click();
        }
    </script>
[{/if}]