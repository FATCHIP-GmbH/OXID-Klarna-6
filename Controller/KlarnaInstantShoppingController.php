<?php


namespace TopConcepts\Klarna\Controller;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\BasketAdapter;
use TopConcepts\Klarna\Core\Exception\InvalidShippingException;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\InstantShopping\HttpClient;
use TopConcepts\Klarna\Core\KlarnaUserManager;
use TopConcepts\Klarna\Model\KlarnaPayment;

class KlarnaInstantShoppingController extends BaseCallbackController
{
    /** @var HttpClient */
    protected $httpClient;

    /** @var  DatabaseInterface */
    protected $db;

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
        $this->db = DatabaseProvider::getDb();
    }

    /**
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function placeOrder()
    {

        $oBasket = Registry::getSession()->getBasket();        // create new basket
        $oBasket->setPayment(KlarnaPayment::KLARNA_INSTANT_SHOPPING);

        $userManager = oxNew(KlarnaUserManager::class);
        $oUser = $userManager->initUser($this->requestData);

        /** @var BasketAdapter $basketAdapter */
        $basketAdapter = oxNew(
            BasketAdapter::class,
            $oBasket,
            $oUser,
            $this->actionData['order']
        );

        $this->db->startTransaction();
        try {
            $basketAdapter
                ->buildBasketFromOrderData()
                ->validateItems()
                //TODO: basket sum validation
            ;
        } catch (OutOfStockException | ArticleInputException | NoArticleException | InvalidShippingException $exception) {
            $this->declineOrder($exception);
            $this->db->rollbackTransaction();
            return;
        }

        //TODO: create order with pending status

        try {

            $resp = $this->approveOrder();
        } catch (KlarnaClientException $exception) {
            // handle 404 and other errors
            $this->db->rollbackTransaction();
            return;
        }

        $this->db->commitTransaction();
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
     * @return array|bool|mixed
     */
    protected function declineOrder($exception)
    {
        $declineData = [
            'deny_message' => $exception->getMessage(),
            'deny_redirect_url' => '',
            'deny_code' => ''
        ];

        return $this->httpClient->declineOrder(
            $this->actionData['authorization_token'],
            $declineData
        );
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

    /**
     * Request Mock
     * @return array
     */
    protected function getRequestData()
    {
        $body = file_get_contents(OX_BASE_PATH . '../klarna_requests/place_order.json');
        return (array)json_decode($body, true);
    }

}