<?php

namespace TopConcepts\Klarna\Controllers;


use OxidEsales\Eshop\Core\Registry as oxRegistry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\UtilsView;

/**
 * Class KlarnaBasketController
 * @package TopConcepts\Klarna\Controllers
 */
class KlarnaBasketController extends KlarnaBasketController_parent
{
    /**
     * Rendering template
     *
     * @return mixed
     */
    public function render()
    {
        if(oxRegistry::get(Request::class)->getRequestEscapedParameter('openAmazonLogin')){
            $this->addTplParam('openAmazonLogin', true);
        }

        $oSession = oxRegistry::getSession();
        $oBasket = $oSession->getBasket();
        $klarnaInvalid = oxRegistry::get(Request::class)->getRequestEscapedParameter('klarnaInvalid');
        if($oBasket->getPaymentId() === 'klarna_checkout' && $klarnaInvalid){
            $this->displayKlarnaValidationErrors();
        }

        return parent::render();
    }

    /**
     *
     * @codeCoverageIgnore
     */
    protected function displayKlarnaValidationErrors()
    {
        parse_str($_SERVER['QUERY_STRING'], $query);

        $oLang          = oxRegistry::getLang();
        foreach($query as $errorId => $articleId){
            if(strstr($errorId, 'ERROR')){
                oxRegistry::get(UtilsView::class)->addErrorToDisplay(
                    sprintf($oLang->translateString($errorId), $articleId)
                );
            }
        }
    }
}