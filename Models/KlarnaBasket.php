<?php

namespace TopConcepts\Klarna\Models;


use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Exception\KlarnaBasketTooLargeException;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\DeliveryList;
use OxidEsales\Eshop\Application\Model\DeliverySet;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\OrderArticle;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;


/**
 * OXID default class oxBasket extensions to add Klarna related logic
 */
class KlarnaBasket extends KlarnaBasket_parent
{
    /**
     * Array of articles that have long delivery term so Klarna cannot be used to pay for them
     *
     * @var array
     */
    protected $_aPreorderArticles = array();

    /**
     * @var string
     */
    protected $_orderHash = '';

    /**
     * Checkout configuration
     * @var array
     */
    protected $_aCheckoutConfig;

    /**
     * Klarna Order Lines
     * @var array
     */
    protected $klarnaOrderLines;

    /**
     * @var int
     */
    protected $klarnaOrderLang;

    /**
     * Format products for Klarna checkout
     *
     * @param bool $orderMgmtId
     * @return array
     * @throws KlarnaBasketTooLargeException
     * @throws \OxidEsales\Eshop\Core\Exception\ArticleInputException
     * @throws \OxidEsales\Eshop\Core\Exception\NoArticleException
     * @internal param $orderData
     */
    public function getKlarnaOrderLines($orderMgmtId = null)
    {
        $this->calculateBasket(true);
        $this->klarnaOrderLines = array();
        $this->_calcItemsPrice();

        if ($orderMgmtId) {
            $oOrder = oxNew(Order::class);
            $oOrder->load($orderMgmtId);
            $iOrderLang = $oOrder->getFieldData('oxlang');
        }

        $aItems = $this->getContents();
        usort($aItems, array($this, 'sortOrderLines'));

        $counter = 0;
        /* @var BasketItem $oItem */
        foreach ($aItems as $oItem) {
            $counter++;

            list($quantity,
                $regular_unit_price,
                $total_amount,
                $total_discount_amount,
                $tax_rate,
                $total_tax_amount,
                $quantity_unit) = KlarnaUtils::calculateOrderAmountsPricesAndTaxes($oItem);

            /** @var Article | BasketItem | KlarnaArticle $oArt */
            $oArt = $oItem->getArticle();
            if (!$oArt instanceof Article) {
                $oArt = $oArt->getArticle();
            }

            $aProcessedItem = array(
                "type"             => "physical",
                'reference'        => $this->getArtNum($oArt),
                'quantity'         => $quantity,
                'unit_price'       => $regular_unit_price,
                'tax_rate'         => $tax_rate,
                "total_amount"     => $total_amount,
                "total_tax_amount" => $total_tax_amount,
            );

            if ($quantity_unit !== '') {
                $aProcessedItem["quantity_unit"] = $quantity_unit;
            }

            if ($total_discount_amount !== 0) {
                $aProcessedItem["total_discount_amount"] = $total_discount_amount;
            }

            $aProcessedItem['name'] = $oArt->kl_getOrderArticleName($counter, $iOrderLang);
            if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {
                $aProcessedItem['product_url']         = $oArt->kl_getArticleUrl();
                $aProcessedItem['image_url']           = $oArt->kl_getArticleImageUrl();
                $aProcessedItem['product_identifiers'] = array(
                    'category_path'            => $oArt->kl_getArticleCategoryPath(),
                    'global_trade_item_number' => $oArt->kl_getArticleEAN(),
                    'manufacturer_part_number' => $oArt->kl_getArticleMPN(),
                    'brand'                    => $oArt->kl_getArticleManufacturer(),
                );
            }

            $this->klarnaOrderLines[] = $aProcessedItem;
        }

        $this->_addServicesAsProducts($orderMgmtId);
        $this->_orderHash = md5(json_encode($this->klarnaOrderLines));

        $totals = $this->calculateTotals($this->klarnaOrderLines);

        $aOrderLines = array(
            'order_lines'      => $this->klarnaOrderLines,
            'order_amount'     => $totals['total_order_amount'],
            'order_tax_amount' => $totals['total_order_tax_amount'],
        );

        $this->_orderHash = md5(json_encode($aOrderLines));

        return $aOrderLines;
    }

    /**
     * @param $aProcessedItems
     * @return array
     * @throws KlarnaBasketTooLargeException
     */
    protected function calculateTotals($aProcessedItems)
    {
        $total_order_amount = $total_order_tax_amount = 0;
        foreach ($aProcessedItems as $item) {
            $total_order_amount     += $item['total_amount'];
            $total_order_tax_amount += $item['total_tax_amount'];
        }

        if ($total_order_amount > 100000000) {
            throw new KlarnaBasketTooLargeException('KL_ORDER_AMOUNT_TOO_HIGH');
        }

        return array(
            'total_order_amount'     => $total_order_amount,
            'total_order_tax_amount' => $total_order_tax_amount,
        );
    }


    /**
     * Add OXID additional payable services as products to array
     * @param bool $orderMgmtId
     * @return void
     */
    protected function _addServicesAsProducts($orderMgmtId = false)
    {
        $iLang  = null;
        $oOrder = null;
        if ($orderMgmtId) {
            $oOrder = oxNew(Order::class);
            $oOrder->load($orderMgmtId);
            $iLang = $oOrder->getFieldData('oxlang');
        }

        if (KlarnaUtils::isKlarnaPaymentsEnabled() || $oOrder) {
            $oDelivery = parent::getCosts('oxdelivery');
//            if ($this->_isServicePriceSet($oDelivery)) {
            $oDeliverySet = oxNew(DeliverySet::class);
            if ($iLang) {
                $oDeliverySet->loadInLang($iLang, $this->getShippingId());
            } else {
                $oDeliverySet->load($this->getShippingId());
            }

            $this->klarnaOrderLines[] = $this->getKlarnaPaymentDelivery($oDelivery, $oOrder, $oDeliverySet);
//            }
        }
        $this->_addDiscountsAsProducts($oOrder, $iLang);
        $this->_addGiftWrappingCost($iLang);
        $this->_addGiftCardProducts($iLang);
//      $this->_addServicePaymentCost();
//      $this->_addTrustedShopsExcellenceFee();

    }

    /**
     * @param null $iLang
     */
    protected function _addGiftWrappingCost($iLang = null)
    {
        /** @var \OxidEsales\Eshop\Core\Price $oWrappingCost */
        $oWrappingCost = $this->getOxWrappingCost();
        if (($oWrappingCost && $oWrappingCost->getPrice())) {
            $unit_price = KlarnaUtils::parseFloatAsInt($oWrappingCost->getBruttoPrice() * 100);

            if (!$this->is_fraction($this->getOrderVatAverage())) {
                $tax_rate = KlarnaUtils::parseFloatAsInt($this->getOrderVatAverage() * 100);
            } else {
                $tax_rate = KlarnaUtils::parseFloatAsInt($oWrappingCost->getVat() * 100);
            }

            $this->klarnaOrderLines[] = array(
                'type'                  => 'physical',
                'reference'             => 'SRV_WRAPPING',
                'name'                  => html_entity_decode(Registry::getLang()->translateString('KL_GIFT_WRAPPING_TITLE', $iLang), ENT_QUOTES),
                'quantity'              => 1,
                'total_amount'          => $unit_price,
                'total_discount_amount' => 0,
                'total_tax_amount'      => KlarnaUtils::parseFloatAsInt(round($oWrappingCost->getVatValue() * 100, 0)),
                'unit_price'            => $unit_price,
                'tax_rate'              => $tax_rate,
            );
        }
    }

    /**
     * @param null $iLang
     */
    protected function _addGiftCardProducts($iLang = null)
    {
        /** @var \OxidEsales\Eshop\Core\Price $oWrappingCost */
        $oGiftCardCost = $this->getCosts('oxgiftcard');
        if (($oGiftCardCost && $oGiftCardCost->getPrice())) {
            $unit_price = KlarnaUtils::parseFloatAsInt($oGiftCardCost->getBruttoPrice() * 100);
            $tax_rate   = KlarnaUtils::parseFloatAsInt($oGiftCardCost->getVat() * 100);

            $this->klarnaOrderLines[] = array(
                'type'                  => 'physical',
                'reference'             => 'SRV_GIFTCARD',
                'name'                  => html_entity_decode(Registry::getLang()->translateString('KL_GIFT_CARD_TITLE', $iLang), ENT_QUOTES),
                'quantity'              => 1,
                'total_amount'          => $unit_price,
                'total_discount_amount' => 0,
                'total_tax_amount'      => KlarnaUtils::parseFloatAsInt($unit_price - round($unit_price / ($tax_rate / 10000 + 1), 0)),
                'unit_price'            => $unit_price,
                'tax_rate'              => $tax_rate,
            );
        }
    }

    /**
     * Add OXID additional discounts as products to array
     * @param null $oOrder
     * @param null $iLang
     * @return void
     */
    protected function _addDiscountsAsProducts($oOrder = null, $iLang = null)
    {
        $oDiscount = $this->getVoucherDiscount();
        if ($this->_isServicePriceSet($oDiscount)) {
            $this->klarnaOrderLines[] = $this->_getKlarnaCheckoutVoucherDiscount($oDiscount, $iLang, $oOrder);
        }

        $oDiscount = $this->getOxDiscount();
        if ($oOrder) {
            $oDiscount = oxNew(Price::class);
            $oDiscount->setBruttoPriceMode();

            $itemDiscountSum = 0;
            foreach ($this->klarnaOrderLines as $item) {
                $itemDiscountSum += $item['total_discount_amount'];
            }
            $oDiscount->setPrice($oOrder->getFieldData('oxdiscount'));
        }
        if ($this->_isServicePriceSet($oDiscount)) {
            $this->klarnaOrderLines[] = $this->_getKlarnaCheckoutDiscount($oDiscount, $iLang, $oOrder);
        }
    }

    /**
     * Check if service is set and has brutto price
     * @param $oService
     *
     * @return bool
     */
    protected function _isServicePriceSet($oService)
    {
        return ($oService && $oService->getBruttoPrice() != 0);
    }

    /**
     * Returns delivery costs
     * @return Price
     */
    protected function getOxDiscount()
    {
        $totalDiscount = oxNew(Price::class);
        $totalDiscount->setBruttoPriceMode();

        $discounts = $this->getDiscounts();
        if (!is_array($discounts)) {
            return $totalDiscount;
        }

        foreach ($discounts as $discount) {
            if ($discount->sType == 'itm') {
                continue;
            }
            $totalDiscount->add($discount->dDiscount);
        }

        return $totalDiscount;
    }

    /**
     * Create klarna checkout product from delivery price
     *
     * @param Price $oPrice
     *
     * @param bool $oOrder
     * @param DeliverySet $oDeliverySet
     * @return array
     */
    public function getKlarnaPaymentDelivery(Price $oPrice, $oOrder = null, DeliverySet $oDeliverySet = null)
    {
        $unit_price = KlarnaUtils::parseFloatAsInt($oPrice->getBruttoPrice() * 100);
        $tax_rate   = KlarnaUtils::parseFloatAsInt($oPrice->getVat() * 100);

        $aItem = array(
            'type'                  => 'shipping_fee',
            'reference'             => 'SRV_DELIVERY',
            'name'                  => html_entity_decode($oDeliverySet->getFieldData('oxtitle'), ENT_QUOTES),
            'quantity'              => 1,
            'total_amount'          => $unit_price,
            'total_discount_amount' => 0,
            'total_tax_amount'      => KlarnaUtils::parseFloatAsInt($unit_price - round($unit_price / ($tax_rate / 10000 + 1), 0)),
            'unit_price'            => $unit_price,
            'tax_rate'              => $tax_rate,
        );

        if ($oOrder && $oOrder->isKCO()) {
            $aItem['reference'] = $oOrder->getFieldData('oxdeltype');
        }

        return $aItem;
    }

    /**
     * Create klarna checkout product from voucher discounts
     *
     * @param Price $oPrice
     * @param null $iLang
     * @return array
     */
    protected function _getKlarnaCheckoutVoucherDiscount(Price $oPrice, $iLang = null)
    {
        $unit_price = -KlarnaUtils::parseFloatAsInt($oPrice->getBruttoPrice() * 100);
        $tax_rate   = KlarnaUtils::parseFloatAsInt($this->_oProductsPriceList->getProportionalVatPercent() * 100);

        $aItem = array(
            'type'             => 'discount',
            'reference'        => 'SRV_COUPON',
            'name'             => html_entity_decode(Registry::getLang()->translateString('KL_VOUCHER_DISCOUNT', $iLang), ENT_QUOTES),
            'quantity'         => 1,
            'total_amount'     => $unit_price,
            'unit_price'       => $unit_price,
            'tax_rate'         => $tax_rate,
            'total_tax_amount' => KlarnaUtils::parseFloatAsInt($unit_price - round($unit_price / ($tax_rate / 10000 + 1), 0)),
        );

        return $aItem;
    }

    /**
     * Create klarna checkout product from non voucher discounts
     *
     * @param Price $oPrice
     * @param null $iLang
     * @return array
     */
    protected function _getKlarnaCheckoutDiscount(Price $oPrice, $iLang = null)
    {
        $unit_price = -KlarnaUtils::parseFloatAsInt($oPrice->getBruttoPrice() * 100);
        $tax_rate   = KlarnaUtils::parseFloatAsInt($this->getOrderVatAverage() * 100);

        $aItem = array(
            'type'             => 'discount',
            'reference'        => 'SRV_DISCOUNT',
            'name'             => html_entity_decode(Registry::getLang()->translateString('KL_DISCOUNT_TITLE', $iLang), ENT_QUOTES),
            'quantity'         => 1,
            'total_amount'     => $unit_price,
            'unit_price'       => -KlarnaUtils::parseFloatAsInt($oPrice->getBruttoPrice() * 100),
            'tax_rate'         => $tax_rate,
            'total_tax_amount' => KlarnaUtils::parseFloatAsInt($unit_price - round($unit_price / ($tax_rate / 10000 + 1), 0)),
        );

        return $aItem;
    }

    /**
     * If param not set, try getting it from config, else - return given param
     *
     * @param int $iInDays
     * @return int
     */
    protected function _getInDays($iInDays = null)
    {
        if ($iInDays === null) {
            // check if feature is not disabled (not 0 or empty)
            if (Registry::getConfig()->getConfigParam('iKlarnaMaxDaysForPreorder')) {
                $iInDays = (int)Registry::getConfig()->getConfigParam('iKlarnaMaxDaysForPreorder');
            }
        }

        return $iInDays;
    }

    /**
     * Original OXID method _calcDeliveryCost
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function kl_calculateDeliveryCost()
    {
        if ($this->_oDeliveryPrice !== null) {
            return $this->_oDeliveryPrice;
        }
        $myConfig       = Registry::getConfig();
        $oDeliveryPrice = oxNew(Price::class);

        if (Registry::getConfig()->getConfigParam('blDeliveryVatOnTop')) {
            $oDeliveryPrice->setNettoPriceMode();
        } else {
            $oDeliveryPrice->setBruttoPriceMode();
        }

        // don't calculate if not logged in
        $oUser = $this->getBasketUser();
        if (!$oUser && !$myConfig->getConfigParam('blCalculateDelCostIfNotLoggedIn')) {
            return $oDeliveryPrice;
        }

        $fDelVATPercent = $this->getAdditionalServicesVatPercent();
        $oDeliveryPrice->setVat($fDelVATPercent);

        // list of active delivery costs
        if ($myConfig->getConfigParam('bl_perfLoadDelivery')) {
            /** @var DeliveryList Create new oxDeliveryList to get proper content */
            $oDeliveryList = oxNew(DeliveryList::class);
            $aDeliveryList = $oDeliveryList->getDeliveryList(
                $this,
                $oUser,
                $this->_findDelivCountry(),
                $this->getShippingId()
            );

            if (count($aDeliveryList) > 0) {
                foreach ($aDeliveryList as $oDelivery) {
                    //debug trace
                    if ($myConfig->getConfigParam('iDebug') == 5) {
                        echo("Delivery Cost : " . $oDelivery->oxdelivery__oxtitle->value . "<br>");
                    }
                    $oDeliveryPrice->addPrice($oDelivery->getDeliveryPrice($fDelVATPercent));
                }
            }
        }

        return $oDeliveryPrice;
    }

    /**
     * @return object
     */
    protected function _calcDeliveryCost()
    {
        if (KlarnaUtils::isKlarnaPaymentsEnabled()) {
            return $this->kl_calculateDeliveryCost();
        } else {
            return parent::_calcDeliveryCost();
        }
    }

    /**
     * Get average of order VAT
     * @return float
     */
    protected function getOrderVatAverage()
    {
        $vatAvg = ($this->getBruttoSum() / $this->getProductsNetPriceWithoutDiscounts() - 1) * 100;

        return number_format($vatAvg, 2);
    }

    /**
     * Returns sum of product netto prices
     * @return float
     */
    protected function getProductsNetPriceWithoutDiscounts()
    {
        $nettoSum = 0;

        if (!empty($this->_aBasketContents)) {
            foreach ($this->_aBasketContents as $oBasketItem) {
                $nettoSum += $oBasketItem->getPrice()->getNettoPrice();
            }
        }

        return $nettoSum;
    }

    /**
     * @param $oArt
     * @return bool|null|string
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function getArtNum($oArt)
    {
        $original = $oArt->oxarticles__oxartnum->value;
        if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization')) {
            $hash = md5($original);
            if (KlarnaUtils::getShopConfVar('iKlarnaValidation') != 0) {
                $this->addKlarnaAnonymousMapping($oArt->getId(), $hash);
            }

            return $hash;
        }

        return substr($original, 0, 64);
    }

    /**
     * Get wrapping cost
     * @return Price
     */
    protected function getOxWrappingCost()
    {
        // if OXID version < 4.8.0
        if (version_compare($this->getConfig()->getVersion(), '4.8.0') == -1) {
            // _calcBasketWrapping problem, that in old oxid wrapping cost is included gift price, so recalculate wrapping only
            $oWrappingPrice = oxNew(Price::class);
            $oWrappingPrice->setBruttoPriceMode();
            // calculating basket items wrapping
            /** @var BasketItem $oBasketItem */
            foreach ($this->_aBasketContents as $oBasketItem) {
                /** @var Price $oWrapPrice */
                if (($oWrapping = $oBasketItem->getWrapping())) {
                    $oWrapPrice = $oWrapping->getWrappingPrice($oBasketItem->getAmount());
                    $oWrappingPrice->add($oWrapPrice->getBruttoPrice());
                }
            }

            return $oWrappingPrice;
        }

        return $this->getWrappingCostParent();
    }

    /**
     * Calls getWrappingCost method parent
     *
     * @return mixed
     */
    protected function getWrappingCostParent()
    {
        return parent::getWrappingCost();
    }

    /**
     * @param $val
     * @return bool
     */
    protected function is_fraction($val)
    {
        return is_numeric($val) && floor($val) != $val;
    }

    /**
     * @param $iLang
     */
    public function setKlarnaOrderLang($iLang)
    {
        $this->klarnaOrderLang = $iLang;
    }

    /**
     * @param BasketItem $a
     * @param BasketItem $b
     * @return int
     * @throws \oxArticleInputException
     * @throws \oxNoArticleException
     * @throws \oxSystemComponentException
     */
    protected function sortOrderLines(BasketItem $a, BasketItem $b)
    {
        $oArtA = $a->getArticle();
        if (!$oArtA instanceof Article) {
            $oArtA = $oArtA->getArticle();
        }
        $oArtB = $b->getArticle();
        if (!$oArtB instanceof Article) {
            $oArtB = $oArtB->getArticle();
        }

        if (round(hexdec($oArtA->getId()), 3) > round(hexdec($oArtB->getId()), 3)) {
            return 1;
        } else if (round(hexdec($oArtA->getId()), 3) < round(hexdec($oArtB->getId()), 3)) {
            return -1;
        }

        return 0;
    }

    /**
     * @param $artOxid
     * @param $anonArtNum
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function addKlarnaAnonymousMapping($artOxid, $anonArtNum)
    {
        $db = DatabaseProvider::getDb();

        $sql = "INSERT IGNORE INTO kl_anon_lookup(klartnum, oxartid) values(?,?)";
        $db->execute($sql, array($anonArtNum, $artOxid));
    }
}