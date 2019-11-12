<?php


namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\DeliverySet;
use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Application\Model\PaymentList;
use TopConcepts\Klarna\Core\Exception\InvalidShippingException;
use TopConcepts\Klarna\Core\Exception\KlarnaConfigException;

class ShippingAdapter extends BasketCostAdapter
{
    /** @var  */
    protected $selectedShippingSetId;

    protected $oActiveDeliverySet;

    public function addItemToBasket()
    {
        $this->oBasket->setShipping(
            $this->itemData['reference']
        );
    }

    /**
     * Resolves shippingId and related shipping set for basket or order object
     * @param $iLang
     */
    public function prepareItemData($iLang)
    {
        $this->selectedShippingSetId = $this->oBasket->getShippingId();
        if ((bool)$this->oOrder) {
            $this->selectedShippingSetId = $this->oOrder->getFildData('oxdeltype');
        }

        $oDeliverySet = oxNew(DeliverySet::class);
        if ($iLang) {
            $oDeliverySet->loadInLang($iLang, $this->selectedShippingSetId);
        } else {
            $oDeliverySet->load($this->selectedShippingSetId);
        }
        $this->oActiveDeliverySet = $oDeliverySet;

        parent::prepareItemData($iLang);

        return $this;
    }

    public function getName()
    {
        return html_entity_decode($this->oActiveDeliverySet->getFieldData('oxtitle'), ENT_QUOTES);
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
        $allSets  = $this->getShippingSets();
        $currency = Registry::getConfig()->getActShopCurrencyObject();
        $shippingOptions = [];
        if (!is_array($allSets)) {
            return $shippingOptions;
        }

        $this->selectedShippingSetId = $this->oBasket->getShippingId();
        foreach ($allSets as $shippingId => $shippingMethod) {
            $this->oBasket->setShipping($shippingId);
            $oPrice      = $this->oBasket->tcklarna_calculateDeliveryCost();
            $basketPrice = $this->oBasket->getPriceForPayment() / $currency->rate;
            if ($this->isShippingForPayment($shippingId, $paymentId, $basketPrice)) {
                $method = clone $shippingMethod;

                $price             = KlarnaUtils::parseFloatAsInt($oPrice->getBruttoPrice() * 100);
                $tax_rate          = KlarnaUtils::parseFloatAsInt($oPrice->getVat() * 100);
                $tax_amount        = KlarnaUtils::parseFloatAsInt($price - round($price / ($tax_rate / 10000 + 1), 0));
                $shippingOptions[] = array(
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

        if (empty($shippingOptions)) {
            $oCountry = oxNew(Country::class);
            $oCountry->load($this->oUser->getActiveCountry());

            throw new KlarnaConfigException(sprintf(
                Registry::getLang()->translateString('TCKLARNA_ERROR_NO_SHIPPING_METHODS_SET_UP'),
                $oCountry->oxcountry__oxtitle->value
            ));
        }

        return empty($shippingOptions) ? null : $shippingOptions;
    }

    /**
     * Validates shipping id and shipping cost
     * Requires calculated basket object
     * @throws InvalidShippingException
     */
    public function validateItem() {
        $isValidShippingId = $this->isShippingForPayment(
            $this->oBasket->getShippingId(),
            $this->oBasket->getPaymentId(),
            $this->oBasket->getPriceForPayment()
        );
        if ($isValidShippingId === false) {
            throw new InvalidShippingException('INVALID_SHIPPING_ID');
        }

        parent::validateItem();
    }
}