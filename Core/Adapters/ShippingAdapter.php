<?php


namespace TopConcepts\Klarna\Core\Adapters;


use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\DeliverySet;
use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Application\Model\PaymentList;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Core\Exception\KlarnaConfigException;
use TopConcepts\Klarna\Core\KlarnaUtils;

class ShippingAdapter extends BasketCostAdapter
{
    /** @var  */
    protected $selectedShippingSetId;

    protected $oDeliverySet;

    protected $shippingOptions;

    /**
     * Resolves shippingId and related shipping set for basket or order object
     * @param $iLang
     */
    public function prepareItemData($iLang)
    {
        $this->selectedShippingSetId = $this->oBasket->getShippingId();
        if ((bool)$this->oOrder) {
            $this->selectedShippingSetId = $this->oOrder->getFieldData('oxdeltype');
        }

        $oDeliverySet = oxNew(DeliverySet::class);
        if ($iLang !== null) {
            $oDeliverySet->loadInLang($iLang, $this->selectedShippingSetId);
        } else {
            $oDeliverySet->load($this->selectedShippingSetId);
        }
        $this->oDeliverySet = $oDeliverySet;

        parent::prepareItemData($iLang);

        return $this;
    }

    public function getName()
    {
        return html_entity_decode($this->oDeliverySet->getFieldData('oxtitle'), ENT_QUOTES);
    }

    public function getReference()
    {
        return $this->selectedShippingSetId;
    }

    protected function getActiveShippingSet()
    {
        $sActShipSet = Registry::get(Request::class)->getRequestEscapedParameter('sShipSet');
        if (!$sActShipSet) {
            $sActShipSet = Registry::getSession()->getVariable('sShipSet');
        }

        return $sActShipSet;
    }

    /**
     *@codeCoverageIgnore  oxid method wrapper, nothing to test here
     */
    protected function getShippingSets()
    {
        list($aAllSets) = Registry::get(DeliverySetList::class)
            ->getDeliverySetData(
                $this->getActiveShippingSet(),
                $this->oUser,
                $this->oBasket
            );

        return $aAllSets;
    }

    /**
     * @param string $shippingId
     * @param $paymentId
     * @param float $basketPrice
     * @return bool
     */
    protected function isShippingForPayment($shippingId, $paymentId, $basketPrice)
    {
        $oPayList    = Registry::get(PaymentList::class);
        $paymentList = $oPayList->getPaymentList($shippingId, $basketPrice, $this->oUser);

        return count($paymentList) && in_array($paymentId, array_keys($paymentList));
    }

    /**
     * @param $paymentId
     * @return array|null
     * @throws KlarnaConfigException
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function getShippingOptions($paymentId)
    {
        if ($this->shippingOptions !== null) {
            return $this->shippingOptions;
        };

        $allSets  = $this->getShippingSets();
        $currency = Registry::getConfig()->getActShopCurrencyObject();
        $this->shippingOptions = [];
        if (!is_array($allSets)) {
            return $this->shippingOptions;
        }

        $this->selectedShippingSetId = $this->oBasket->getShippingId();
        foreach ($allSets as $shippingId => $oDeliverySet) {
            $this->oBasket->setShipping($shippingId);
            $oCost = $this->oBasket->tcklarna_calculateDeliveryCost();
            $basketPrice = $this->oBasket->getPriceForPayment() / $currency->rate;
            if ($this->isShippingForPayment($shippingId, $paymentId, $basketPrice)) {
                $method = clone $oDeliverySet;
                $price             = $this->formatAsInt($oCost->getBruttoPrice());
                $tax_rate          = $this->formatAsInt($oCost->getVat());
                $tax_amount        = $this->calcTax($price, $tax_rate);
                $this->shippingOptions[] = array(
                    "id"          => $shippingId,
                    "name"        => html_entity_decode($method->oxdeliveryset__oxtitle->value, ENT_QUOTES),
                    "description" => '',
                    "tax_amount"  => $tax_amount,
                    'price'       => $price,
                    'tax_rate'    => $tax_rate,
                    'preselected' => $shippingId === $this->selectedShippingSetId ? true : false,
                );
            }
        }

        // set basket back to selected shipping option
        $this->oBasket->setShipping($this->selectedShippingSetId);

        if (empty($this->shippingOptions)) {
            $oCountry = oxNew(Country::class);
            $oCountry->load($this->oUser->getActiveCountry());
            throw new KlarnaConfigException(sprintf(
                Registry::getLang()->translateString('TCKLARNA_ERROR_NO_SHIPPING_METHODS_SET_UP'),
                $oCountry->oxcountry__oxtitle->value
            ));
        }

        return empty($this->shippingOptions) ? null : $this->shippingOptions;
    }

    /**
     * Validates shipping id and shipping cost
     * Requires calculated basket object
     * @param $orderLine
     * @throws InvalidItemException
     */
    public function validateItem($orderLine) {
        $isValidShippingId = $this->isShippingForPayment(
            $orderLine['reference'],
            $this->oBasket->getPaymentId(),
            $this->oBasket->getPriceForPayment()
        );
        KlarnaUtils::log('debug', 'VALIDATING: ' . print_r([
                'type' => $this->getType(),
                'isValid' => $isValidShippingId
            ], true));
        if ($isValidShippingId === false) {
            $oEx = new InvalidItemException("INVALID_SHIPPING_ID");
            $this->errorCode = 'unsupported_shipping_address';
            $oEx->setItemAdapter($this);
            throw $oEx;
        }

        parent::validateItem($orderLine);
    }

    /**
     * @param array $updateData
     * @throws KlarnaConfigException
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function handleUpdate(&$updateData) {
        $updateData['shipping_options'] = $this->getShippingOptions($this->oBasket->getPaymentId());
        KlarnaUtils::log('debug', 'SHIPPING_OPTIONS_RECALCULATED');
    }
}