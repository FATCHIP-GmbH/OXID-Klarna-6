<div class="half-width float-right pull-right">
    [{$smarty.block.parent}]
    <div class="clearfix"></div>
    <div id="instant" >
        [{include file="tcklarna_instant_shopping_button.tpl"}]
    </div>
</div>

<style>
    [{if $oViewConf->isActiveThemeFlow()}]
        #instant{
            margin-top:15px;
            float:right;
        }
        button.nextStep {
            width: 300px;
            padding: 15px;
            border-radius: 0;
        }
    [{else}]
        #instant{
            margin-top:15px;
            width:100% !important;
            float:right;
        }
        .half-width{
            width:50% !important;
        }

        .half-width form{
            width:100%;
        }
        .half-width button.nextStep {
            width:100% !important;
            padding: 15px;
            border-radius: 0;
        }

        @media(max-width:992px){
            .half-width{
                width:100% !important;
            }
        }
    [{/if}]
</style>