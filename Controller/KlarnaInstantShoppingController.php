<?php


namespace TopConcepts\Klarna\Controller;

use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Adapters\BasketAdapter;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\InstantShopping\HttpClient;
use TopConcepts\Klarna\Core\KlarnaUserManager;
use TopConcepts\Klarna\Model\KlarnaInstantBasket;

class KlarnaInstantShoppingController extends BaseCallbackController
{
    const EXECUTE_SUCCESS = 'thankyou';
    const KLARNA_PENDING_STATUS = 'PENDING';
    const NOT_FINISHED_STATUS = 'NOT_FINISHED';

    /** @var HttpClient */
    protected $httpClient;

    /** @var  DatabaseInterface */
    protected $db;

    /** @var KlarnaUserManager */
    protected $userManager;

    protected $actionRules = [
        'placeOrder' => [
            'log' => true,
            'validator' => [
                'order' => ['required', 'notEmpty ', 'extract'],
                'authorization_token' => ['required', 'notEmpty ', 'extract'],
            ]
        ],
        'updateOrder' => [
            'log' => true,
        ]
    ];

    public function init()
    {
        parent::init();
        $this->httpClient = HttpClient::getInstance();
        $this->db = DatabaseProvider::getDb();
        $this->userManager = oxNew(KlarnaUserManager::class);
    }

    /**
     * @throws StandardException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function placeOrder()
    {
        $this->userManager->initUser($this->requestData['order']);
        $basketAdapter = $this->createBasketAdapter();
        if ($basketAdapter === false) {
            return;
        }
        $this->db->startTransaction();
        try {
            $basketAdapter->buildOrderLinesFromBasket();
            $basketAdapter->validateOrderLines();
            $orderId = $this->prepareOrderExecution();
            /** @var OrderController $oOrderController */
            $oOrderController = Registry::get(OrderController::class);
            $result = $oOrderController->execute();

            if ($result !== self::EXECUTE_SUCCESS) {
                throw new StandardException('INVALID_ORDER_EXECUTE_RESULT: ' . $result);
            }

            $klarnaResponse = $this->approveOrder();
            $oOrder = oxNew(Order::class);
            $oOrder->load($orderId);

            if($klarnaResponse['fraud_status'] == self::KLARNA_PENDING_STATUS) {
                $oOrder->oxorder__oxtransstatus = new Field(self::NOT_FINISHED_STATUS, Field::T_RAW);
            }

            $oOrder->oxorder__tcklarna_orderid = new Field($klarnaResponse['order_id'], Field::T_RAW);
            $oOrder->save();
            $basketAdapter->finalizeBasket($orderId);

        } catch (\Exception $exception) {
            Registry::getLogger()->log('error', $exception->getMessage());
            try {
                $this->declineOrder($exception);
            } catch (KlarnaClientException $exception) {
                Registry::getLogger()->log('error', 'ORDER_NOT_FOUND: ' . $exception->getMessage());
            }
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

    public function prepareOrderExecution()
    {
        $sToken = Registry::getSession()->getVariable('sess_stoken');
        $_GET['stoken'] = $sToken;

        $sDelAddress = $this->getUser()->getEncodedDeliveryAddress();
        // delivery address
        if (\OxidEsales\Eshop\Core\Registry::getSession()->getVariable('deladrid')) {
            $oDelAddress = oxNew(\OxidEsales\Eshop\Application\Model\Address::class);
            $oDelAddress->load(\OxidEsales\Eshop\Core\Registry::getSession()->getVariable('deladrid'));

            $sDelAddress .= $oDelAddress->getEncodedDeliveryAddress();
        }
        $_GET['sDeliveryAddressMD5'] = $sDelAddress;

        $orderId = Registry::getUtilsObject()->generateUID();
        Registry::getSession()->setVariable('sess_challenge', $orderId);

        return $orderId;

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
        $this->userManager->initUser($this->requestData);
        $basketAdapter = $this->createBasketAdapter();
        if ($basketAdapter === false) {
            return;
        }
        /** @var BasketAdapter $basketAdapter */
        try {
            $basketAdapter->buildOrderLinesFromBasket();
            $basketAdapter->setHandleBasketUpdates(true);
            $basketAdapter->validateOrderLines();
        } catch (\Exception $exception) {
            Registry::getLogger()->log('error', $exception->getMessage());
            http_response_code(304);
            exit;
        }

        $updateData = $basketAdapter->getUpdateData();
        if ($updateData) {
            $this->sendResponse($updateData);
        }

//        var_dump($basketAdapter);
//        if($this->requestData['update_context'] == "identification_updated") {//User info and address change
//            $basketAdapter->buildOrderLinesFromBasket();
//            $orderLines = $basketAdapter->getOrderData();
//            $this->db->commitTransaction();
//            $this->updateResponse(json_encode($orderLines));
//
//            exit;
//        }
//
//        if($this->requestData['update_context'] == "specifications_selected") {//Product changes
//            $this->db->commitTransaction();
//            $this->updateResponse('{"shipping_options": [{
//                        "id": "oxidstandard",
//                        "name": "DHL",
//                        "description": "DHL Standard Versand",
//                        "price": 100,
//                        "tax_amount": 10,
//                        "tax_rate": 1000,
//                        "preselected": true,
//                        "shipping_method": "BoxReg"
//                    }]}');
//
//            exit;
//        }
        http_response_code(304);
        exit;
    }

    /**
     * @return false|BasketAdapter
     */
    protected function createBasketAdapter()
    {
        // Fetch saved Instant Shopping basket
        $instantShoppingBasketId = $this->actionData['order']['merchant_data'];
        $oInstantShoppingBasket = oxNew(KlarnaInstantBasket::class);
        if ($oInstantShoppingBasket->load($instantShoppingBasketId) === false) {
            return false;
        }
        $oBasket = $oInstantShoppingBasket->getBasket();
        Registry::getSession()->setBasket($oBasket);
        /** @var BasketAdapter $basketAdapter */
        $basketAdapter = oxNew(
            BasketAdapter::class,
            $oBasket,
            $this->getUser(),
            $this->actionData['order']
        );

        $basketAdapter->setInstantShoppingBasket($oInstantShoppingBasket);

        return $basketAdapter;
    }

    protected function sendResponse($data)
    {
        header('Content-Type: application/json');
        http_response_code(304);
        echo json_encode($data);
        Registry::getLogger()->log('debug', 'UPDATE_SEND: ' .
            print_r($data, true)
        );
        exit;
    }


    /**
     * Request Mock
     * @return array
     */
    protected function getRequestData()
    {
        if ($_GET['mock']) {
            $mockType = $_GET['fnc'];
            $body = file_get_contents(OX_BASE_PATH . "../klarna_requests/{$mockType}.json");
            return (array)json_decode($body, true);
        }
        $original = parent::getRequestData();

        $multiply = function(&$item, $m) {
            $f = ['quantity', 'total_amount', 'total_tax_amount', 'total_discount_amount'];
            foreach($f as $field) {
                $item[$field] = $item[$field] * $m;
            }
        };
        $multiply($original['order_lines'][0], 4);

        return $original;
    }
}