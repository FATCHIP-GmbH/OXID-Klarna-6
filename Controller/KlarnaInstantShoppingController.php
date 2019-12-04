<?php


namespace TopConcepts\Klarna\Controller;

use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Adapters\BasketAdapter;
use TopConcepts\Klarna\Core\Exception\InvalidOrderExecuteResult;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Core\Exception\KlarnaBasketTooLargeException;
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
                'merchant_reference2' => ['required', 'notEmpty ', 'extract']
            ]
        ],
        'updateOrder' => [
            'log' => true,
            'validator' => [
                'update_context' => ['required', 'notEmpty ', 'extract'],
                'merchant_reference2' => ['required', 'notEmpty ', 'extract']
            ]
        ],
        'successAjax' => [
            'log' => false
        ],
        'startSessionAjax' => [
            'log' => false,
            'validator' => [
                'merchant_reference2' => ['required', 'notEmpty ', 'extract'],
            ]
        ]
    ];

    protected $validContextList = [
        'identification_updated',
        'specifications_selected'
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
        /** @var BasketAdapter $basketAdapter */
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
                throw $this->extractOrderException($result);

            }
            $oOrder = oxNew(Order::class);
            $oOrder->load($orderId);
            $response = $this->approveOrder($oOrder);
            $this->updateOrderObject($oOrder, $response);
            $basketAdapter->closeBasket($orderId);
        } catch (KlarnaClientException $approveOrderException) {
            Registry::getLogger()->log('error', 'ORDER_NOT_FOUND: ' . $approveOrderException->getMessage());
        } catch (\Exception $exception) {
            try {
                $this->declineOrder($exception);
            } catch (KlarnaClientException $declineOrderException) {
                Registry::getLogger()->log('error', 'ORDER_NOT_FOUND: ' . $declineOrderException->getMessage());
            }
            $this->db->rollbackTransaction();
            return;
        }
        $this->db->commitTransaction();
    }

    /**
     * @param Order $oOrder
     * @return array|bool|mixed
     */
    protected function approveOrder(Order $oOrder)
    {
        $this->actionData['order']['merchant_reference1'] = $oOrder->oxorder__oxordernr->value;
        $this->actionData['order']['merchant_reference2'] = "";
        return $this->httpClient->approveOrder(
            $this->actionData['authorization_token'],
            $this->actionData['order']
        );
    }

    protected function prepareOrderExecution()
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
        Registry::getConfig()->setConfigParam('blConfirmAGB', 0);

        return $orderId;
    }


    /**
     * @param $exception \Exception
     * @return array|bool|mixed
     */
    protected function declineOrder($exception)
    {
        $code = 'other';
        $messageId = 'TCKLARNA_IS_ERROR_DEFAULT';

        if ($exception instanceof KlarnaBasketTooLargeException) {
            $messageId = $exception->getMessage();
        }

        if ($exception instanceof InvalidItemException) {
            $oItemAdapter = $exception->getItemAdapter();
            $code = $oItemAdapter->getErrorCode();
        }

        if ($exception instanceof InvalidOrderExecuteResult) {
            $type = $exception->getType();
            if ($type !== null) {
                // oxOutOfStockException
                // oxArticleInputException
                $code = 'item_out_of_stock';
            }
        }

        // address_error
        // consumer_underaged

        $iLang = $this->getLangId();
        $declineData = [
            'deny_message' => Registry::getLang()->translateString($messageId, $iLang),
            'deny_redirect_url' => '',
            'deny_code' => $code
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
        if (in_array($this->actionData['update_context'], $this->validContextList) === false) {
            return;
        }
        $basketAdapter = $this->createBasketAdapter();
        if ($basketAdapter === false) {
            return;
        }

        try {
            $basketAdapter->buildOrderLinesFromBasket();
            $basketAdapter->setHandleBasketUpdates(true);
            $basketAdapter->validateOrderLines();
            $basketAdapter->storeBasket();
        } catch (\Exception $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
            return;
        }

        $updateData = $basketAdapter->getUpdateData();
        if ($updateData) {
            $this->sendResponse($updateData);
        }
    }

    /**
     * @return false|BasketAdapter
     */
    protected function createBasketAdapter()
    {
        // Fetch saved Instant Shopping basket
        $instantShoppingBasketId = $this->actionData['order']['merchant_reference2'];
        $oInstantShoppingBasket = oxNew(KlarnaInstantBasket::class);
        if ($oInstantShoppingBasket->load($instantShoppingBasketId) === false) {
            return false;
        }
//        $oInstantShoppingBasket->getOxuserId();
//        $this->actionData['order']['userId'] = $oInstantShoppingBasket->getOxuserId();
//        $this->userManager->initUser($this->actionData['order']);


        /** @var Basket $oBasket */
        $oBasket = $oInstantShoppingBasket->getBasket();
        if(!empty($this->actionData['order']['selected_shipping_option']['id'])) {
            $oBasket->setShipping($this->actionData['order']['selected_shipping_option']['id']);
        }

        $userEmail = $this->actionData['order']['billing_address']['email'];
        if(!empty($userEmail)) {
            $user = oxNew(User::class);
            $user->loadByEmail($userEmail);
            $oBasket->setBasketUser($user);
        }

        $this->userManager->initUser($this->actionData['order'], $oBasket->getBasketUser());

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

    protected function updateOrderObject(Order $oOrder, $approveResponse)
    {
        if($approveResponse['fraud_status'] == self::KLARNA_PENDING_STATUS) {
            $oOrder->oxorder__oxtransstatus = new Field(self::NOT_FINISHED_STATUS, Field::T_RAW);
        }

        $oOrder->oxorder__tcklarna_orderid = new Field($approveResponse['order_id'], Field::T_RAW);
        $oOrder->saveMerchantIdAndServerMode();
        $oOrder->save();
    }

    public function successAjax()
    {
        $result = false;
        $instantShoppingBasketId = Registry::getSession()->getVariable('instant_shopping_basket_id');
        if ($instantShoppingBasketId) {
            /** @var KlarnaInstantBasket $oInstantShoppingBasket */
            $oInstantShoppingBasket = oxNew(KlarnaInstantBasket::class);
            if ($oInstantShoppingBasket->load($instantShoppingBasketId) && $oInstantShoppingBasket->isFinalized()) {
                if ($oInstantShoppingBasket->getType() === KlarnaInstantBasket::TYPE_BASKET) {
                    $result = true;
                    Registry::getSession()->getBasket()->deleteBasket(); // remove session basket and item reservations
                }
            }
        }
        $this->sendResponse(['result' => (int)$result]);
    }

    /**
     * @param $result
     * @return InvalidOrderExecuteResult
     */
    protected function extractOrderException($result) {
        $orderException = new InvalidOrderExecuteResult('INVALID_ORDER_EXECUTE_RESULT: ' . print_r($result, true));
        $errors = Registry::getSession()->getVariable('Errors');
        foreach ($errors as $location => $serializedExceptions) {
            foreach ($serializedExceptions as $serializedException) {
                /** @var  ExceptionToDisplay $oException */
                $oException = unserialize($serializedException);
                $orderException->setType($oException->getErrorClassType());
                $orderException->setValues($oException->getValues());
                break;
            }
        }
        return $orderException;
    }

    /**
     * Return language id
     * @return int
     */
    protected function getLangId()
    {
        $locale = $this->actionData['order']['locale'];
        $langAbbr = reset(explode('-', $locale));
        $langIds = Registry::getLang()->getLanguageIds();

        return  array_search($langAbbr, $langIds) ?: 0;
    }

    public function startSessionAjax()
    {
        Registry::getSession()->setVariable('instant_shopping_basket_id', $this->actionData['merchant_reference2']);
    }
}