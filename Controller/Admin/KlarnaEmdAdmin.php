<?php

namespace TopConcepts\Klarna\Controller\Admin;


use TopConcepts\Klarna\Core\KlarnaConsts;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Field;

/**
 * Class Klarna_Config for module configuration in OXID backend
 */
class KlarnaEmdAdmin extends KlarnaBaseConfig
{

    protected $_sThisTemplate = 'tcklarna_emd_admin.tpl';

    /**
     * Render logic
     *
     * @see admin/oxAdminDetails::render()
     * @return string
     */
    public function render()
    {
        // force shopid as parameter
        // Pass shop OXID so that shop object could be loaded
        $sShopOXID = Registry::getConfig()->getShopId();

        $this->setEditObjectId($sShopOXID);

        $this->addTplParam('activePayments', $this->getPaymentList());

        parent::render();

        return $this->_sThisTemplate;
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        parent::save();

        $vars    = $this->_oRequest->getRequestEscapedParameter('payments');
        /** @var Payment $payment */
        $payment = oxNew(Payment::class);

        foreach ($vars as $oxid => $settings) {
            $payment->load($oxid);
            foreach ($settings as $key => $value) {
                $payment->{$key} = new Field($value, Field::T_RAW);
            }
            $payment->save();
        }
    }

    /**
     * @return array
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function getPaymentList()
    {
        $paymentIds = $this->getAllActiveOxPaymentIds();

        $payments = array();
        foreach ($paymentIds as $oxid) {
            $payments[] = $this->getPaymentData($oxid['oxid']);
        }

        return $payments;
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getEmdPaymentTypeOptions()
    {
        return KlarnaConsts::getEmdPaymentTypeOptions();
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getFullHistoryOrdersOptions()
    {
        return KlarnaConsts::getFullHistoryOrdersOptions();
    }

}