<?php
namespace Klarna\Klarna\Controllers;

use OxidEsales\Eshop\Core\Registry as oxRegistry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\UtilsView;


class KlarnaBasketController extends KlarnaBasketController_parent
{
    /**
     * Rendering template
     *
     * @return mixed
     */
    public function render()
    {
        if(oxRegistry::get(Request::class)->getRequestParameter('openAmazonLogin')){
            $this->addTplParam('openAmazonLogin', true);
        }

        $oSession = oxRegistry::getSession();
        $oBasket = $oSession->getBasket();
        $klarnaInvalid = oxRegistry::get(Request::class)->getRequestParameter('klarnaInvalid');
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
                oxRegistry::get(UtilsView::class)->addErrorToDisplay(
                    sprintf($oLang->translateString($errorId), $articleId)
                );
            }
        }
    }
}