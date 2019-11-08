<?php


namespace TopConcepts\Klarna\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\InstantShopping\HttpClient;
use TopConcepts\Klarna\Core\KlarnaUserManager;

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

//            $userManager = new KlarnaUserManager();
//            $userManager->initUser($order);

            $httpClient = HttpClient::getInstance();
            $httpClient->approveOrder($info['authorization_token'], $order);
        }

        return Registry::getUtils()->showMessageAndExit(json_encode(array(
            'status' => 'ok'
        )));
    }

    public function updateOrder()
    {
        $postJson = file_get_contents("php://input");
        $info = json_decode($postJson, true);
        if($info['update_context'] == "identification_updated") {
            header('Content-Type: application/json');
            echo '{"shipping_options": [{
                        "id": "oxidstandard",
                        "name": "DHL",
                        "description": "DHL Standard Versand",
                        "price": 100,
                        "tax_amount": 10,
                        "tax_rate": 1000,
                        "preselected": true,
                        "shipping_method": "BoxReg"
                    }]}';

            exit;
        }

        http_response_code(304);

        exit;
    }

}