<script type="text/javascript" src="[{ $oViewConf->getModuleUrl('tcklarna', 'out/src/js/libs/jquery-1.12.4.min.js') }]"></script>
<script type="text/javascript" src="[{ $sAmazonWidgetUrl|cat:'?sellerId='|cat:$sAmazonSellerId }]"></script>
[{capture append="oxidBlock_content"}]
    <style>
        #breadCrumb { display: none;}
        .outer {
            height: 300px;
            position: relative;
            border: 1px solid #cbcbcb;
            border-radius: 10px;
        }
        .middle {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 600px;
        }
        .widget-wrapper {
            padding: 20px;
        }
    </style>
    <div class="outer">
        <div class="middle">
            <h1>[{"TCKLARNA_LOGIN_INTO_AMAZON"|oxmultilangassign}]</h1>
            <div class="widget-wrapper" id="_amazonLoginButton"></div>
        </div>
    </div>
    <script type="text/javascript">
        var initObj;
        (function () {
            amazon.Login.setClientId('[{$oViewConf->getAmazonConfigValue('sAmazonLoginClientId')}]');

            [{assign var="aButtonStyle" value="-"|explode:$oViewConf->getAmazonConfigValue('sAmazonLoginButtonStyle')}]
            var authRequest;
            initObj = {
                type: '[{$aButtonStyle.0}]',
                size: ($('meta[name=apple-mobile-web-app-capable]').attr("content")=='yes') ? 'medium' : 'small',
                color: '[{$aButtonStyle.1}]',
                language: '[{$oViewConf->getAmazonLanguage()}]',

                authorization: function() {
                    loginOptions =  {scope: 'profile payments:widget payments:shipping_address', popup: true};
                    authRequest = amazon.Login.authorize(loginOptions, function(response) {
                        addressConsentToken = response.access_token;
                        window.location = '[{$oViewConf->getSslSelfLink()|html_entity_decode}]cl=user&fnc=amazonLogin&redirectCl=user&' + '&access_token=' + addressConsentToken;

                    });
                },

                onSignIn: function(orderReference) {
                    amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
                    window.location = '[{$oViewConf->getSslSelfLink()|html_entity_decode}]cl=user&amazonOrderReferenceId=' + amazonOrderReferenceId;
                },
                onError: function(error) {
                    window.location = '[{$oViewConf->getSslSelfLink()|html_entity_decode}]cl=basket&amazonOrderReferenceId=' + amazonOrderReferenceId;
                },
            };

            OffAmazonPayments.Button(
                '_amazonLoginButton',
                '[{$oViewConf->getAmazonConfigValue('sAmazonSellerId')}]',
                initObj
            );

            // amazon.Login.setSandboxMode(true);
            // initObj.authorization();


        })();
    </script>
[{/capture}]

[{include file="layout/page.tpl"}]