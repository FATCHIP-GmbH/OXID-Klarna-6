<?php


namespace TopConcepts\Klarna\Controller;

use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\BasketAdapter;
use TopConcepts\Klarna\Core\InstantShopping\HttpClient;

class KlarnaInstantShoppingController extends BaseCallbackController
{
    /** @var HttpClient */
    protected $httpClient;

    protected $actionRules = [
        'placeOrder' => [
            'log' => true,
            'validator' => [
                'order' => ['required', 'notEmpty ', 'extract'],
                'authorization_token' => ['required', 'notEmpty ', 'extract'],
            ],
        ],
    ];

    public function init()
    {
        parent::init();
        $this->httpClient = HttpClient::getInstance();
    }

    public function placeOrder()
    {
        // create new basket
        $oBasket = Registry::getSession()->getBasket();
        $basketAdapter = oxNew(
            BasketAdapter::class,
            $oBasket,
            $this->actionData['order']
        );

        try {
            $basketAdapter
                ->fillBasketFromOrderData()
                ->validateItems()
                ->validateShipping();

        } catch (OutOfStockException | ArticleInputException | NoArticleException $exception) {
            $this->declineOrder($exception);
            return;
        }

        $this->approveOrder();
        return;
    }

    protected function approveOrder()
    {
        return $this->httpClient->approveOrder(
            $this->actionData['authorization_token'],
            $this->actionData['order']
        );
    }


    /**
     * @param $exception \Exception
     */
    protected function declineOrder($exception)
    {
        echo $exception->getMessage();
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

//    /**
//     * Request Mock
//     * @return array
//     */
//    protected function getRequestData()
//    {
//        $body = '
//{
//  "authorization_token": "870ce23e-86e7-4b84-affa-b34feb50c63c",
//  "button_key": "426eb5ea-7fd1-4cae-b86b-6083c6323a9c",
//  "order": {
//    "name": "skonhetsmagasinet",
//    "purchase_country": "DE",
//    "purchase_currency": "EUR",
//    "locale": "de-DE",
//    "billing_address": {
//      "given_name": "JÃ¶rg",
//      "family_name": "WeiÃ",
//      "email": "ferreira@topconcepts.com",
//      "title": "Herr",
//      "street_address": "Karnapp 25/1",
//      "postal_code": "21079",
//      "city": "Hamburg",
//      "phone": "+4930306900",
//      "country": "DE"
//    },
//    "shipping_address": {
//      "given_name": "JÃ¶rg",
//      "family_name": "WeiÃ",
//      "email": "ferreira@topconcepts.com",
//      "title": "Herr",
//      "street_address": "Karnapp 25/1",
//      "postal_code": "21079",
//      "city": "Hamburg",
//      "phone": "+4930306900",
//      "country": "DE"
//    },
//    "order_amount": 126250,
//    "order_tax_amount": 25250,
//    "order_lines": [
//      {
//        "type": "physical",
//        "reference": "0702-85-853-6-3",
//        "name": "Kuyichi Jeans ANNA",
//        "quantity": 1,
//        "unit_price": 9290,
//        "tax_rate": 1900,
//        "total_amount": 9290,
//        "total_discount_amount": 0,
//        "total_tax_amount": 1483,
//        "image_url": "http://demohost.topconcepts.net/arek/klarna/ce_620/source/out/pictures/generated/product/1/540_340_75/front_z1(4)sb.jpg"
//      },
//      {
//        "quantity": 1,
//        "unit_price": 1250,
//        "total_amount": 1250,
//        "type": "shipping_fee",
//        "reference": "oxidstandard",
//        "name": "DHL",
//        "tax_rate": 2500,
//        "total_tax_amount": 250,
//        "product_attributes": []
//      }
//    ],
//    "merchant_urls": {
//      "terms": "https://demoklarnacheckout.topconcepts.com/4_2_1/ce_4103/_terms.html",
//      "confirmation": "http://demohost.topconcepts.net/hugo/klarna/ce_613/source/out/test.html",
//      "place_order": "https://engu58jr2lr4p.x.pipedream.net/"
//    },
//    "customer": {
//      "date_of_birth": "1980-01-01",
//      "title": "Herr",
//      "gender": "male"
//    },
//    "integrator_url": "http://demohost.topconcepts.net/hugo/klarna/ce_613/source/out/test.html",
//    "selected_shipping_option": {
//      "id": "oxidstandard",
//      "name": "DHL",
//      "price": 1250,
//      "tax_amount": 250,
//      "tax_rate": 2500,
//      "preselected": false,
//      "shipping_method": "box-reg"
//    }
//  }
//}
//';
//        return (array)json_decode($body, true);
//    }

}