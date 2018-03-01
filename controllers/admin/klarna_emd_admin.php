<?php

/**
 * Class Klarna_Config for module configuration in OXID backend
 */
class Klarna_Emd_Admin extends klarna_base_config
{

    protected $_sThisTemplate = 'kl_klarna_emd_admin.tpl';

    /**
     * Render logic
     *
     * @see admin/oxAdminDetails::render()
     * @return string
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    public function render()
    {
        // force shopid as parameter
        // Pass shop OXID so that shop object could be loaded
        $sShopOXID = oxRegistry::getConfig()->getShopId();

        $this->setEditObjectId($sShopOXID);

        $this->addTplParam('activePayments', $this->getPaymentList());

        parent::render();

        return $this->_sThisTemplate;
    }

    /**
     * @throws oxSystemComponentException
     */
    public function save()
    {
        parent::save();

        $vars    = oxRegistry::getConfig()->getRequestParameter('payments');
        $payment = oxNew('oxPayment');

        foreach ($vars as $oxid => $settings) {
            $payment->load($oxid);
            foreach ($settings as $key => $value) {
                $payment->{$key} = new oxField($value, oxField::T_RAW);
            }
            $payment->save();
        }
    }

    /**
     * @return array
     * @throws oxConnectionException
     * @throws oxSystemComponentException
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
     */
    public function getEmdPaymentTypeOptions()
    {
        return KlarnaConsts::getEmdPaymentTypeOptions();
    }

    /**
     * @return array
     */
    public function getFullHistoryOrdersOptions()
    {
        return KlarnaConsts::getFullHistoryOrdersOptions();
    }

}