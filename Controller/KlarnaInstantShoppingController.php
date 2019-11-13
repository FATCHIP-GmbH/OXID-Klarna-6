<?php


namespace TopConcepts\Klarna\Controller;

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
        'updateOrder' => [
            'log' => true,
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
        $basketAdapter = $this->createBasketAdapter();

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

    /**
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException
     */
    public function updateOrder()
    {
        $this->actionData['order'] = $this->requestData;
        /** @var BasketAdapter $basketAdapter */
        $basketAdapter = $this->createBasketAdapter();

        $this->db->startTransaction();
        try {
            $basketAdapter->buildBasketFromOrderData();
//            $basketAdapter->validateItems();
        } catch (OutOfStockException | ArticleInputException | NoArticleException | InvalidShippingException $exception) {
            //roll back
            $this->db->rollbackTransaction();
            http_response_code(304);
            exit;
        }

        if($this->requestData['update_context'] == "identification_updated") {//User info and address change
            $basketAdapter->buildOrderLinesFromBasket();
            $orderLines = $basketAdapter->getOrderData();
            $this->db->commitTransaction();
            $this->updateResponse(json_encode($orderLines));

            exit;
        }

        if($this->requestData['update_context'] == "specifications_selected") {//Product changes
            $this->db->commitTransaction();
            $this->updateResponse('{"shipping_options": [{
                        "id": "oxidstandard",
                        "name": "DHL",
                        "description": "DHL Standard Versand",
                        "price": 100,
                        "tax_amount": 10,
                        "tax_rate": 1000,
                        "preselected": true,
                        "shipping_method": "BoxReg"
                    }]}');

            exit;
        }


    }

//    /**
//     * Request Mock
//     * @return array
//     */
//    protected function __getRequestData()
//    {
//        $body = file_get_contents(OX_BASE_PATH . '../klarna_requests/place_order.json');
//        return (array)json_decode($body, true);
//    }

    protected function createBasketAdapter()
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

        return $basketAdapter;
    }

    protected function updateResponse($json)
    {
        header('Content-Type: application/json');
        echo $json;
        exit;
    }

}