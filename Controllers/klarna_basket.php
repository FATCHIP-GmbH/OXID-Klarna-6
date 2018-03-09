<?php

class klarna_basket extends klarna_basket_parent
{
    /**
     * Rendering template
     *
     * @return mixed
     */
    public function render()
    {
        if(oxRegistry::getConfig()->getRequestParameter('openAmazonLogin')){
            $this->addTplParam('openAmazonLogin', true);
        }

        $oSession = oxRegistry::getSession();
        $oBasket = $oSession->getBasket();
        $klarnaInvalid = oxRegistry::getConfig()->getRequestParameter('klarnaInvalid');
        if($oBasket->getPaymentId() === 'klarna_checkout' && $klarnaInvalid){
            $this->displayKlarnaValidationErrors();
        }

        return parent::render();
    }


    protected function displayKlarnaValidationErrors()
    {
        parse_str($_SERVER['QUERY_STRING'], $query);

        $oLang          = oxRegistry::getLang();
        foreach($query as $errorId => $articleId){
            if(strstr($errorId, 'ERROR')){
                oxRegistry::get("oxUtilsView")->addErrorToDisplay(
                    sprintf($oLang->translateString($errorId), $articleId)
                );
            }
        }
    }
}