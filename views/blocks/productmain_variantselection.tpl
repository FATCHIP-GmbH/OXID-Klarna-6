[{$smarty.block.parent}]


[{oxscript add="$('.selectorsBox a').on('change',refreshKlarnaMessage());"}]
<script>
    function refreshKlarnaMessage() {
        window.KlarnaOnsiteService = window.KlarnaOnsiteService || [];  // Making sure that data layer exists in case JavaScript Library is loaded later for any reason
        window.KlarnaOnsiteService.push({eventName: 'refresh-placements'}); // Push the event to the data layer
    }
</script>


