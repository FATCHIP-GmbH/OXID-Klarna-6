<?php
/**
 * Copyright 2018 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace TopConcepts\Klarna\Model;


use OxidEsales\Eshop\Core\Config;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Core\Exception\KlarnaBasketTooLargeException;
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

        $iOrderLang = $this->getOrderLang($orderMgmtId);

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
                $quantity_unit) = KlarnaUtils::calculateOrderAmountsPricesAndTaxes($oItem, $orderMgmtId);

            /** @var Article | BasketItem | KlarnaArticle $oArt */
            $oArt = $oItem->getArticle();
            if (!$oArt instanceof Article) {
                $oArt = $oArt->getArticle();
            }

            if ($iOrderLang) {
                $oArt->loadInLang($iOrderLang, $oArt->getId());
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

            $aProcessedItem['name'] = $oArt->tcklarna_getOrderArticleName($counter, $iOrderLang);
            if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {
                $aProcessedItem['product_url']         = $oArt->tcklarna_getArticleUrl();
                $aProcessedItem['image_url']           = $oArt->tcklarna_getArticleImageUrl();
                $aProcessedItem['product_identifiers'] = array(
                    'category_path'            => $oArt->tcklarna_getArticleCategoryPath(),
                    'global_trade_item_number' => $oArt->tcklarna_getArticleEAN(),
                    'manufacturer_part_number' => $oArt->tcklarna_getArticleMPN(),
                    'brand'                    => $oArt->tcklarna_getArticleManufacturer(),
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

    protected function getOrderLang($orderMgmtId)
    {
        $iOrderLang = null;
        if ($orderMgmtId) {
            $oOrder = oxNew(Order::class);
            $oOrder->load($orderMgmtId);
            $iOrderLang = $oOrder->getFieldData('oxlang');
        }

        return $iOrderLang;
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
            throw new KlarnaBasketTooLargeException('TCKLARNA_ORDER_AMOUNT_TOO_HIGH');
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
            $oDelivery    = parent::getCosts('oxdelivery');
            $oDeliverySet = oxNew(DeliverySet::class);
            if ($iLang) {
                $oDeliverySet->loadInLang($iLang, $this->getShippingId());
            } else {
                $oDeliverySet->load($this->getShippingId());
            }

            $this->klarnaOrderLines[] = $this->getKlarnaPaymentDelivery($oDelivery, $oOrder, $oDeliverySet);
        }
        $this->_addDiscountsAsProducts($oOrder, $iLang);
        $this->_addGiftWrappingCost($iLang);
        $this->_addGiftCardProducts($iLang);
    }

    /**
     * @param null $iLang
     */
    protected function _addGiftWrappingCost($iLang = null)
    {
        /** @var \OxidEsales\Eshop\Core\Price $oWrappingCost */
        $oWrappingCost = $this->getWrappingCost();
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
                'name'                  => html_entity_decode(Registry::getLang()->translateString('TCKLARNA_GIFT_WRAPPING_TITLE', $iLang), ENT_QUOTES),
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
                'name'                  => html_entity_decode(Registry::getLang()->translateString('TCKLARNA_GIFT_CARD_TITLE', $iLang), ENT_QUOTES),
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
            'name'             => html_entity_decode(Registry::getLang()->translateString('TCKLARNA_VOUCHER_DISCOUNT', $iLang), ENT_QUOTES),
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
            'name'             => html_entity_decode(Registry::getLang()->translateString('TCKLARNA_DISCOUNT_TITLE', $iLang), ENT_QUOTES),
            'quantity'         => 1,
            'total_amount'     => $unit_price,
            'unit_price'       => -KlarnaUtils::parseFloatAsInt($oPrice->getBruttoPrice() * 100),
            'tax_rate'         => $tax_rate,
            'total_tax_amount' => KlarnaUtils::parseFloatAsInt($unit_price - round($unit_price / ($tax_rate / 10000 + 1), 0)),
        );

        return $aItem;
    }


    /**
     * Original OXID method _calcDeliveryCost
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function tcklarna_calculateDeliveryCost()
    {
        if ($this->_oDeliveryPrice !== null) {
            return $this->_oDeliveryPrice;
        }
        $myConfig       = Registry::getConfig();
        $oDeliveryPrice = oxNew(Price::class);

        Registry::getConfig()->getConfigParam('blDeliveryVatOnTop')?$oDeliveryPrice->setNettoPriceMode():$oDeliveryPrice->setBruttoPriceMode();

        // don't calculate if not logged in
        $oUser = $this->getBasketUser();

        if (!$oUser && !$myConfig->getConfigParam('blCalculateDelCostIfNotLoggedIn')) {
            return $oDeliveryPrice;
        }

        $fDelVATPercent = $this->getAdditionalServicesVatPercent();
        $oDeliveryPrice->setVat($fDelVATPercent);

        // list of active delivery costs
        $this->handleDeliveryCosts($myConfig,$oUser,$oDeliveryPrice,$fDelVATPercent);

        return $oDeliveryPrice;
    }

    protected function handleDeliveryCosts(Config $myConfig, $oUser, Price &$oDeliveryPrice, $fDelVATPercent)
    {
        // list of active delivery costs
        if ($myConfig->getConfigParam('bl_perfLoadDelivery')) {
            /** @var DeliveryList oDeliveryList */
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
                        echo("Delivery Cost : " . $oDelivery->oxdelivery__oxtitle->value . "<br>"); // @codeCoverageIgnore
                    }
                    $oDeliveryPrice->addPrice($oDelivery->getDeliveryPrice($fDelVATPercent));
                }
            }
        }
    }

    /**
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     * @return object
     */
    protected function _calcDeliveryCost()
    {
        return KlarnaUtils::isKlarnaPaymentsEnabled()?$this->tcklarna_calculateDeliveryCost():parent::_calcDeliveryCost();
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
     * @param $val
     * @return bool
     */
    protected function is_fraction($val)
    {
        return is_numeric($val) && fmod($val, 1);
    }

    /**
     * @codeIgnoreCoverage
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

        $sql = "INSERT IGNORE INTO tcklarna_anon_lookup(tcklarna_artnum, oxartid) values(?,?)";
        $db->execute($sql, array($anonArtNum, $artOxid));
    }

    /**
     * Check if vouchers are still valid. Usually used in the ajax requests
     */
    public function klarnaValidateVouchers()
    {
        $this->_calcVoucherDiscount();
    }
}