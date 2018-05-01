[{$smarty.block.parent}]

[{if $oViewConf->isCheckoutNonKlarnaCountry() && $oView->getIsOrderStep() }]
    [{capture assign="otherCountry"}]
        <div class="col-lg-2">
            <button type="button"
                    class="btn btn-default js-other-country">[{oxmultilang ident="TCKLARNA_OTHER_COUNTRY"}]</button>
        </div>
    [{/capture}]
    <script type="text/javascript">
        var otherCountryButton = '[{$otherCountry|trim|escape:javascript}]';
    </script>
    [{capture assign="dataCollectionJS"}]
        function decodeEntities(encodedString) {
            var textArea = document.createElement('textarea');
            textArea.innerHTML = encodedString;
            return textArea.value;
        }

        $('.js-other-country').on('click', function(){
            $form = $(this).closest('form');
            $form.find('input[name="fnc"]').val('klarnaResetCountry');
            $form.find('input[name="cl"]').val('user');
            var formdata = $form.serialize();
            $.post($form.attr('action'), formdata, function(url){
                window.location.href = decodeEntities(url);
            });
        });
    [{/capture}]

    [{oxscript add='var $dropdown = $("#invCountrySelect").parent();
$dropdown.removeClass("col-lg-9").addClass("col-lg-7");
var $otherCountry = $.parseHTML(otherCountryButton);
$dropdown.parent().append($otherCountry);' priority=10}]
    [{oxscript add=$dataCollectionJS priority=10}]
[{/if}]