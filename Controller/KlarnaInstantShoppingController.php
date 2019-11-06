<?php


namespace TopConcepts\Klarna\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\InstantShopping\HttpClient;

class KlarnaInstantShoppingController extends FrontendController
{

    public function placeOrder()
    {
        $postJson = file_get_contents("php://input");
        $info = json_decode($postJson, true);

        if(!empty($info)) {
            //remove unecessary info
            unset($info['order']["selected_shipping_option"]);
            unset($info['order']["merchant_urls"]);
            unset($info['order']["name"]);

            //prepare and make request
            $order = $info['order'];

            $httpClient = HttpClient::getInstance();
            $httpClient->approveOrder($info['authorization_token'], $order);
        }

        return Registry::getUtils()->showMessageAndExit(json_encode(array(
            'status' => 'ok'
        )));
    }

}