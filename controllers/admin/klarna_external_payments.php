<?php

/**
 * Class Klarna_Config for module configuration in OXID backend
 */
class Klarna_External_Payments extends klarna_base_config
{

    protected $_sThisTemplate = 'kl_klarna_external_payments.tpl';

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

        parent::render();

        $this->addTplParam('mode', $this->getActiveKlarnaMode());
        $this->addTplParam('activePayments', $this->getPaymentList());
        $this->addTplParam('paymentNames', KlarnaConsts::getKlarnaExternalPaymentNames());

        return $this->_sThisTemplate;
    }

    /**
     * @return array
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    public function getPaymentList()
    {
        $db = oxdb::getDb(oxDb::FETCH_MODE_ASSOC);

        $sql = 'SELECT oxid 
                FROM oxpayments
                WHERE oxid NOT LIKE "klarna%"
                AND oxid != "oxempty"
                AND oxactive = 1';

        $oxids = $db->select($sql);

        $payments = array();
        foreach ($oxids as $oxid) {
            $payments[] = $this->getPaymentData($oxid['oxid']);
        }

        return $payments;
    }

    /**
     * @throws oxSystemComponentException
     */
    public function save()
    {
        $vars    = oxRegistry::getConfig()->getRequestParameter('payments');
        $payment = oxNew('oxPayment');
        $payment->setEnableMultilang(false);
        foreach ($vars as $oxid => $settings) {
            $payment->load($oxid);
            foreach ($settings as $key => $value) {
                $payment->{$key} = new oxField($value, oxField::T_RAW);
            }
            $payment->save();
        }
    }

    /**
     * Ajax endpoint for multilang input fields
     */
    public function getMultilangUrls()
    {
        $langs         = array_keys(oxRegistry::getLang()->getLanguageIds());
        $fields        = array(
            'oxpayments__klpaymentimageurl',
            'oxpayments__klcheckoutimageurl',
        );
        $imageUrls = array();
        foreach ($this->getPaymentList() as $payment) {
            $oPayment = oxNew('oxPayment');
            $oPayment->setEnableMultilang(false);
            $oPayment->load($payment['oxid']);
            foreach ($langs as $langId) {
                $langSuffix = $langId == 0 ? '' : '_' . $langId;
                foreach ($fields as $field) {
                    $imageUrls[] = array(
                        'name'  => 'payments[' . $oPayment->getId() . '][' . $field . $langSuffix . ']',
                        'value' => $oPayment->{$field . $langSuffix}->value
                    );
                }
            }
        }
        $multiLangData['imageUrls'] = $imageUrls;
        $multiLangData['errorMsg'] = array(
            'valueMissing' => oxRegistry::getLang()->translateString('KL_EXTERNAL_IMAGE_URL_EMPTY'),
            'patternMismatch' => oxRegistry::getLang()->translateString('KL_EXTERNAL_IMAGE_URL_INVALID')
        );

        oxRegistry::getUtils()->showMessageAndExit(json_encode($multiLangData));
    }

    /**
     * @return string
     */
    protected function getActiveKlarnaMode()
    {
        return KlarnaUtils::getShopConfVar('sKlarnaActiveMode');
    }
}