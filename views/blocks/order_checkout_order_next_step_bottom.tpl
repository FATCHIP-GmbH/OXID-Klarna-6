[{ $smarty.block.parent }]

[{if $payment->oxpayments__oxid->value !== 'bestitamazon'}]
    <script type="javascript/text">
        if(typeof amazon !== 'undefined')
            delete amazon;
    </script>
[{/if}]