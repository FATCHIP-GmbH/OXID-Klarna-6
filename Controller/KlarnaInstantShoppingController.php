<?php


namespace TopConcepts\Klarna\Controller;

use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Adapters\BasketAdapter;
use TopConcepts\Klarna\Core\Exception\InvalidOrderExecuteResult;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Core\Exception\KlarnaBasketTooLargeException;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\InstantShopping\Button;
use TopConcepts\Klarna\Core\InstantShopping\HttpClient;
use TopConcepts\Klarna\Core\InstantShopping\PaymentHandler;
use TopConcepts\Klarna\Core\KlarnaLogs;
use TopConcepts\Klarna\Core\KlarnaUserManager;
use TopConcepts\Klarna\Model\KlarnaInstantBasket;

class KlarnaInstantShoppingController extends BaseCallbackController
{
    const EXECUTE_SUCCESS = 'thankyou';

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
        'endSessionAjax' => [
            'log' => false,
            'validator' => [
                'merchant_reference2' => ['required', 'notEmpty ', 'extract'],
            ]
        ],
        'startSessionAjax' => [
            'log' => false,
            'validator' => [
                'merchant_reference2' => ['required', 'notEmpty ', 'extract'],
                'order_lines' => ['required', 'notEmpty ', 'extract'],
            ]
        ]
    ];

    protected $validContextList = [
        'identification_updated',
        'specifications_selected',
        'session_updated'
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
            $basketAdapter->closeBasket($orderId);

        } catch (\Exception $exception) {
            $this->logError($exception);
            try {
                $this->declineOrder($exception);
            } catch (KlarnaClientException $declineOrderException) {
                $this->logOrderNotFound($declineOrderException);
            }
            $this->db->rollbackTransaction();
            return;
        }
        $this->db->commitTransaction();
    }

    /**
     * @codeCoverageIgnore
     */
    protected function logError($exception)
    {
        Registry::getLogger()->error('[INSTANT SHOPPING]'.$exception->getMessage(), [$exception]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function logOrderNotFound($declineOrderException)
    {
        Registry::getLogger()->log('error', 'ORDER_NOT_FOUND: ' . $declineOrderException->getMessage());
    }

    protected function prepareOrderExecution()
    {
        $sToken = Registry::getSession()->getVariable('sess_stoken');
        $_GET['stoken'] = $sToken;

        $sDelAddress = $this->getUser()->getEncodedDeliveryAddress();
        // delivery address
        if (Registry::getSession()->getVariable('deladrid')) {
            $sDelAddress .= $this->getDelAddress();
        }

        $_GET['sDeliveryAddressMD5'] = $sDelAddress;

        $orderId = Registry::getUtilsObject()->generateUID();
        Registry::getSession()->setVariable('sess_challenge', $orderId);
        Registry::getConfig()->setConfigParam('blConfirmAGB', false);
        Registry::getConfig()->setConfigParam('blEnableIntangibleProdAgreement', false);

        // store order details, we will need that later inside
        // Order::execute > PaymentGateway::executePayment > PaymentHandler::executePayment
        Registry::getConfig()->setConfigParam(PaymentHandler::ORDER_CONTEXT_KEY, $this->actionData);

        return $orderId;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getDelAddress()
    {
        $oDelAddress = oxNew(Address::class);
        $oDelAddress->load(Registry::getSession()->getVariable('deladrid'));

        return $oDelAddress->getEncodedDeliveryAddress();
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
            if (in_array($exception->getType(), ['oxOutOfStockException', 'oxArticleInputException'])) {
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
            Registry::getLogger()->error('[INSTANT SHOPPING UPDATE]'.$exception->getMessage(), [$exception]);
            return;
        }

        $updateData = $basketAdapter->getUpdateData();
        if ($updateData) {
            $oLog = new KlarnaLogs();
            $oLog->logData(
                $this->getFncName(),
                $this->requestData,
                'callback',
                '',
                $updateData,
                0
            );
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
        /** @var  KlarnaInstantBasket $oInstantShoppingBasket */
        $oInstantShoppingBasket = Registry::get(KlarnaInstantBasket::class);
        if ($oInstantShoppingBasket->load($instantShoppingBasketId) === false) {
            return false;
        }

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

    public function endSessionAjax()
    {
        $result = false;
        $instantShoppingBasketId = $this->actionData['merchant_reference2'];
        Registry::getSession()->setVariable('instant_shopping_basket_id', $instantShoppingBasketId);
        if ($instantShoppingBasketId) {
            /** @var KlarnaInstantBasket $oInstantShoppingBasket */
            $oInstantShoppingBasket = Registry::get(KlarnaInstantBasket::class);
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
        if (count($errors) > 0) {
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

        $values = [];
        $aUrl = parse_url($result);
        parse_str((string)$aUrl['query'], $values);
        $orderException->setType((string)$aUrl['path']);
        $orderException->setValues($values);

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

    /**
     *  if not exists creates KlarnaInstantBasket record
     *  Sends created object id in the response
     * @throws \Exception
     */
    public function startSessionAjax()
    {
        // try to load IS basket
        $oInstantShoppingBasket = Registry::get(KlarnaInstantBasket::class);
        $loaded = $oInstantShoppingBasket->load($this->actionData['merchant_reference2']);
        if ($loaded === false) {

            // prepare product object for KlarnaInstantBasket::TYPE_SINGLE_PRODUCT
            $oProduct = null;
            $type = $this->actionData['merchant_reference2'];
            $oButton = Registry::get(Button::class);
            if ($type === KlarnaInstantBasket::TYPE_SINGLE_PRODUCT) {
                $artNum = $this->actionData['order_lines'][0]['reference'];
                $oProduct = Registry::get(Article::class);
                $oProduct->klarna_loadByArtNum($artNum);
            }
            // create IS basket
            /** @var BasketAdapter $oBasketAdapter */
            $oBasketAdapter = $oButton->instantiateBasketAdapter($oProduct);
            $oBasketAdapter->storeBasket($type);

            $this->sendResponse(['merchant_reference2' => $oBasketAdapter->getMerchantData()]);
        }
    }
}