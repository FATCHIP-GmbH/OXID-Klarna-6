<?php

class Klarna_oxOrder extends Klarna_oxOrder_parent
{

    protected $isAnonymous;

    /**
     * Validates order parameters like stock, delivery and payment
     * parameters
     *
     * @param oxbasket $oBasket basket object
     * @param oxuser $oUser order user
     *
     * @return bool|null|void
     */
    public function validateOrder($oBasket, $oUser)
    {
        if ($oBasket->getPaymentId() == 'klarna_checkout') {
            return $this->_klarnaValidate($oBasket);
        } else {
            $_POST['sDeliveryAddressMD5'] = oxRegistry::getSession()->getVariable('sDelAddrMD5');

            return parent::validateOrder($oBasket, $oUser);
        }
    }

    /**
     * Validate Klarna Checkout order information
     * @param $oBasket
     * @return int
     */
    protected function _klarnaValidate($oBasket)
    {
        // validating stock
        $iValidState = $this->validateStock($oBasket);

        if (!$iValidState) {
            // validating delivery
            $iValidState = $this->validateDelivery($oBasket);
        }

        if (!$iValidState) {
            // validating payment
            $iValidState = $this->validatePayment($oBasket);
        }

        if (!$iValidState) {
            // validating minimum price
            $iValidState = $this->validateBasket($oBasket);
        }


        return $iValidState;
    }

    /**
     * @return mixed
     * @throws oxSystemComponentException
     */
    protected function _setNumber()
    {
        if ($blUpdate = parent::_setNumber()) {

            /** @var oxSession $session */
            if (in_array($this->oxorder__oxpaymenttype->value, klarnaOxPayment::getKlarnaPaymentsIds())
                && empty($this->oxorder__klorderid->value)) {

                $session = oxRegistry::getSession();

                if ($this->isKP()) {
                    $klarna_id = $session->getVariable('klarna_last_KP_order_id');
                    $session->deleteVariable('klarna_last_KP_order_id');
                }

                if ($this->isKCO()) {
                    $klarna_id = $session->getVariable('klarna_checkout_order_id');
                }

                $this->oxorder__klorderid = new oxField($klarna_id, oxfield::T_RAW);

                $this->saveMerchantIdAndServerMode();

                $this->save();

                try {
                    $sCountryISO = KlarnaUtils::getCountryISO($this->getFieldData('oxbillcountryid'));
                    $orderClient = $this->getKlarnaClient($sCountryISO);
                    $orderClient->sendOxidOrderNr($this->oxorder__oxordernr->value, $klarna_id);
                } catch (oxException $e) {
                    $e->debugOut();
                }
            }
        }

        return $blUpdate;
    }

    /**
     *
     * @throws oxSystemComponentException
     */
    protected function saveMerchantIdAndServerMode()
    {
        $sCountryISO = KlarnaUtils::getCountryISO($this->getFieldData('oxbillcountryid'));

        $aKlarnaCredentials = KlarnaUtils::getAPICredentials($sCountryISO);
        $test               = KlarnaUtils::getShopConfVar('blIsKlarnaTestMode');

        preg_match('/(?<mid>^[a-zA-Z0-9]+)/', $aKlarnaCredentials['mid'], $matches);
        $mid        = $matches['mid'];
        $serverMode = $test ? 'playground' : 'live';

        $this->oxorder__klmerchantid = new oxField($mid, oxfield::T_RAW);
        $this->oxorder__klservermode = new oxField($serverMode, oxfield::T_RAW);
    }

    /**
     * @return bool
     */
    public function isKP()
    {
        return in_array($this->oxorder__oxpaymenttype->value, klarnaOxPayment::getKlarnaPaymentsIds('KP'));
    }

    /**
     * @return bool
     */
    public function isKCO()
    {
        return $this->oxorder__oxpaymenttype->value === klarnaOxPayment::KLARNA_PAYMENT_CHECKOUT_ID;
    }

    /**
     * @return bool
     */
    public function isKlarna()
    {
        return in_array($this->oxorder__oxpaymenttype->value, klarnaOxPayment::getKlarnaPaymentsIds());
    }

    /**
     * Check if order is Klarna order
     *
     * @return boolean
     */
    public function isKlarnaOrder()
    {
        if (strstr($this->getFieldData('oxpaymenttype'), 'klarna_')) {
            return true;
        }

        return false;
    }

    /**
     * Performs standard order cancellation process
     *
     * @return void
     * @throws oxException
     */
    public function cancelOrder()
    {
        // check if it is Klarna order and not already canceled
        if ($this->isKlarnaOrder() && !$this->getFieldData('oxstorno') && $this->getFieldData('klsync') == 1) {
            $orderId     = $this->getFieldData('klorderid');
            $sCountryISO = KlarnaUtils::getCountryISO($this->getFieldData('oxbillcountryid'));
            try {
                $result = $this->cancelKlarnaOrder($orderId, $sCountryISO);
            } catch (KlarnaWrongCredentialsException $e) {
                if (strstr($e->getMessage(), 'is canceled.')) {
                    parent::cancelOrder();
                } else {
//                    oxRegistry::get('oxUtilsView')->addErrorToDisplay(
//                        oxRegistry::getLang()->translateString("KLARNA_UNAUTHORIZED_REQUEST")
//                    );
                    return oxRegistry::getLang()->translateString("KLARNA_UNAUTHORIZED_REQUEST");
//                    $this->showKlarnaErrorMessage($e);
                }

                return;
            } catch (KlarnaOrderNotFoundException $e) {
//                oxRegistry::get('oxUtilsView')->addErrorToDisplay(
//                    oxRegistry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND")
//                );

//                $this->showKlarnaErrorMessage($e);

                return oxRegistry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND");

            } catch (oxException $e) {

                return $this->showKlarnaErrorMessage($e);
            }

        }

        parent::cancelOrder();
    }

    /**
     * @param $orderId
     * @param null $sCountryISO
     * @return mixed
     * @throws KlarnaClientException
     * @throws oxException
     */
    public function cancelKlarnaOrder($orderId = null, $sCountryISO = null)
    {
        $orderId = $orderId ?: $this->getFieldData('klorderid');

        $client = $this->getKlarnaClient($sCountryISO);

        return $client->cancelOrder($orderId);
    }

    /**
     * @param $sCountryISO
     * @return KlarnaOrderManagementClient|KlarnaClientBase
     */
    public function getKlarnaClient($sCountryISO = null)
    {
        return KlarnaOrderManagementClient::getInstance($sCountryISO);
    }

    /**
     * @param $data
     * @param $orderId
     * @param $sCountryISO
     * @return void
     */
    public function updateKlarnaOrder($data, $orderId, $sCountryISO = null)
    {
        $client = $this->getKlarnaClient($sCountryISO);
        try {
            $result                = $client->updateOrderLines($data, $orderId);
            $this->oxorder__klsync = new oxField(1);
            $this->save();
        } catch (KlarnaWrongCredentialsException $e) {
            $this->oxorder__klsync = new oxField(0, oxField::T_RAW);
            $this->save();

            return oxRegistry::getLang()->translateString("KLARNA_UNAUTHORIZED_REQUEST");
//            oxRegistry::get('oxUtilsView')->addErrorToDisplay(
//                oxRegistry::getLang()->translateString("KLARNA_UNAUTHORIZED_REQUEST")
//            );
//

//
//            $this->showKlarnaErrorMessage($e);

        } catch (KlarnaOrderNotFoundException $e) {
            $this->oxorder__klsync = new oxField(0, oxField::T_RAW);
            $this->save();

            return oxRegistry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND");

//            oxRegistry::get('oxUtilsView')->addErrorToDisplay(
//                oxRegistry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND")
//            );
//

//
//            $this->showKlarnaErrorMessage($e);

        } catch (oxException $e) {

            $this->oxorder__klsync = new oxField(0, oxField::T_RAW);
            $this->save();

            return $this->showKlarnaErrorMessage($e);
        }
    }

    /**
     * @param $data
     * @param $orderId
     * @param null $sCountryISO
     * @return array
     * @throws KlarnaClientException
     * @throws oxException
     * @throws oxSystemComponentException
     */
    public function captureKlarnaOrder($data, $orderId, $sCountryISO = null)
    {
        if ($trackcode = $this->getFieldData('oxtrackcode')) {
            $data['shipping_info'] = array(array('tracking_number' => $trackcode));
        }
        $client = $this->getKlarnaClient($sCountryISO);

        return $client->captureOrder($data, $orderId);
    }

    /**
     * @param $orderId
     * @param null $sCountryISO
     * @return array
     * @throws KlarnaClientException
     * @throws oxException
     */
    public function getAllCaptures($orderId, $sCountryISO = null)
    {
        $client = $this->getKlarnaClient($sCountryISO);

        return $client->getAllCaptures($orderId);
    }

    /**
     * @param $orderId
     * @param null $sCountryISO
     * @return mixed
     * @throws KlarnaClientException
     * @throws oxException
     */
    public function retrieveKlarnaOrder($orderId, $sCountryISO = null)
    {
        $client = $this->getKlarnaClient($sCountryISO);

        return $client->getOrder($orderId);
    }

    /**
     * @param $data
     * @param $orderId
     * @param null $sCountryISO
     * @return array
     * @throws KlarnaClientException
     * @throws oxException
     * @throws oxSystemComponentException
     */
    public function createOrderRefund($data, $orderId, $sCountryISO = null)
    {
        $client = $this->getKlarnaClient($sCountryISO);

        return $client->createOrderRefund($data, $orderId);
    }

    /**
     * @param $data
     * @param $orderId
     * @param $captureId
     * @param null $sCountryISO
     * @return array
     * @throws KlarnaClientException
     * @throws oxException
     * @throws oxSystemComponentException
     */
    public function addShippingToCapture($data, $orderId, $captureId, $sCountryISO = null)
    {
        $client = $this->getKlarnaClient($sCountryISO);

        return $client->addShippingToCapture($data, $orderId, $captureId);
    }

    /**
     * Formats pdf page footer.
     *
     * @param object $oPdf pdf document object
     */
    public function pdfFooter($oPdf)
    {
        $oShop = $this->_getActShop();

        $oPdf->line(15, 272, 195, 272);

        $oPdfBlock = new InvoicepdfBlock();
        /* column 1 - company name, shop owner info, shop address */
        $oPdf->setFont($oPdfBlock->getFont(), '', 7);
        $oPdf->text(15, 275, strip_tags($oShop->oxshops__oxcompany->getRawValue()));
        $oPdf->text(15, 278, strip_tags($oShop->oxshops__oxfname->getRawValue()) . ' ' . strip_tags($oShop->oxshops__oxlname->getRawValue()));
        $oPdf->text(15, 281, strip_tags($oShop->oxshops__oxstreet->getRawValue()));
        $oPdf->text(15, 284, strip_tags($oShop->oxshops__oxzip->value) . ' ' . strip_tags($oShop->oxshops__oxcity->getRawValue()));
        $oPdf->text(15, 287, strip_tags($oShop->oxshops__oxcountry->getRawValue()));

        /* column 2 - phone, fax, url, email address */
        $oPdf->text(85, 275, $this->translate('ORDER_OVERVIEW_PDF_PHONE') . strip_tags($oShop->oxshops__oxtelefon->value));
        $oPdf->text(85, 278, $this->translate('ORDER_OVERVIEW_PDF_FAX') . strip_tags($oShop->oxshops__oxtelefax->value));
        $oPdf->text(85, 281, strip_tags($oShop->oxshops__oxurl->value));
        $oPdf->text(85, 284, strip_tags($oShop->oxshops__oxorderemail->value));

        /* column 3 - bank information */
        if (strstr($this->oxorder__oxpaymenttype->value, 'klarna_')) {
            $oPdf->text(150, 275, $this->translate('ORDER_OVERVIEW_PDF_VATID') . strip_tags($oShop->oxshops__oxvatnumber->value));
            $oPdf->text(150, 278, $this->translate('ORDER_OVERVIEW_PDF_TAXID') . strip_tags($oShop->oxshops__oxtaxnumber->value));
        } else {
            $oPdf->text(150, 275, strip_tags($oShop->oxshops__oxbankname->getRawValue()));
            $oPdf->text(150, 278, $this->translate('ORDER_OVERVIEW_PDF_ACCOUNTNR') . strip_tags($oShop->oxshops__oxbanknumber->value));
            $oPdf->text(150, 281, $this->translate('ORDER_OVERVIEW_PDF_BANKCODE') . strip_tags($oShop->oxshops__oxbankcode->value));
            $oPdf->text(150, 284, $this->translate('ORDER_OVERVIEW_PDF_VATID') . strip_tags($oShop->oxshops__oxvatnumber->value));
            $oPdf->text(150, 287, $this->translate('ORDER_OVERVIEW_PDF_TAXID') . strip_tags($oShop->oxshops__oxtaxnumber->value));
        }
    }


    /**
     * Exporting standard invoice pdf
     *
     * @param object $oPdf pdf document object
     * @throws oxSystemComponentException
     */
    public function exportStandart($oPdf)
    {
        // preparing order curency info
        $myConfig  = $this->getConfig();
        $oPdfBlock = new InvoicepdfBlock();

        $this->_oCur = $myConfig->getCurrencyObject($this->oxorder__oxcurrency->value);
        if (!$this->_oCur) {
            $this->_oCur = $myConfig->getActShopCurrencyObject();
        }

        // loading active shop
        $oShop = $this->_getActShop();

        // shop information
        $oPdf->setFont($oPdfBlock->getFont(), '', 6);
        $oPdf->text(15, 55, $oShop->oxshops__oxname->getRawValue() . ' - ' . $oShop->oxshops__oxstreet->getRawValue() . ' - ' . $oShop->oxshops__oxzip->value . ' - ' . $oShop->oxshops__oxcity->getRawValue());

        // billing address
        $this->_setBillingAddressToPdf($oPdf);

        // delivery address
        if ($this->oxorder__oxdelsal->value) {
            $this->_setDeliveryAddressToPdf($oPdf);
        }

        // loading user
        $oUser = oxNew('oxuser');
        $oUser->load($this->oxorder__oxuserid->value);

        // user info
        $sText = $this->translate('ORDER_OVERVIEW_PDF_FILLONPAYMENT');
        $oPdf->setFont($oPdfBlock->getFont(), '', 5);
        $oPdf->text(195 - $oPdf->getStringWidth($sText), 55, $sText);

        // customer number
        $sCustNr = $this->translate('ORDER_OVERVIEW_PDF_CUSTNR') . ' ' . $oUser->oxuser__oxcustnr->value;
        $oPdf->setFont($oPdfBlock->getFont(), '', 7);
        $oPdf->text(195 - $oPdf->getStringWidth($sCustNr), 59, $sCustNr);

        // setting position if delivery address is used
        if ($this->oxorder__oxdelsal->value) {
            $iTop = 115;
        } else {
            $iTop = 91;
        }

        // shop city
        $sText = $oShop->oxshops__oxcity->getRawValue() . ', ' . date('d.m.Y', strtotime($this->oxorder__oxbilldate->value));
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);
        $oPdf->text(195 - $oPdf->getStringWidth($sText), $iTop + 8, $sText);

        // shop VAT number
        if ($oShop->oxshops__oxvatnumber->value) {
            $sText = $this->translate('ORDER_OVERVIEW_PDF_TAXIDNR') . ' ' . $oShop->oxshops__oxvatnumber->value;
            $oPdf->text(195 - $oPdf->getStringWidth($sText), $iTop + 12, $sText);
            $iTop += 8;
        } else {
            $iTop += 4;
        }

        // invoice number
        $sText = $this->translate('ORDER_OVERVIEW_PDF_COUNTNR') . ' ' . $this->oxorder__oxbillnr->value;
        $oPdf->text(195 - $oPdf->getStringWidth($sText), $iTop + 8, $sText);

        // marking if order is canceled
        if ($this->oxorder__oxstorno->value == 1) {
            $this->oxorder__oxordernr->setValue($this->oxorder__oxordernr->getRawValue() . '   ' . $this->translate('ORDER_OVERVIEW_PDF_STORNO'), oxField::T_RAW);
        }

        // order number
        $oPdf->setFont($oPdfBlock->getFont(), '', 12);
        $oPdf->text(15, $iTop, $this->translate('ORDER_OVERVIEW_PDF_PURCHASENR') . ' ' . $this->oxorder__oxordernr->value);

        // order date
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);
        $aOrderDate = explode(' ', $this->oxorder__oxorderdate->value);
        $sOrderDate = oxRegistry::get("oxUtilsDate")->formatDBDate($aOrderDate[0]);
        $oPdf->text(15, $iTop + 8, $this->translate('ORDER_OVERVIEW_PDF_ORDERSFROM') . $sOrderDate . $this->translate('ORDER_OVERVIEW_PDF_ORDERSAT') . $oShop->oxshops__oxurl->value);
        $iTop += 16;

        // product info header
        $oPdf->setFont($oPdfBlock->getFont(), '', 8);
        $oPdf->text(15, $iTop, $this->translate('ORDER_OVERVIEW_PDF_AMOUNT'));
        $oPdf->text(30, $iTop, $this->translate('ORDER_OVERVIEW_PDF_ARTID'));
        $oPdf->text(45, $iTop, $this->translate('ORDER_OVERVIEW_PDF_DESC'));
        $oPdf->text(135, $iTop, $this->translate('ORDER_OVERVIEW_PDF_VAT'));
        $oPdf->text(148, $iTop, $this->translate('ORDER_OVERVIEW_PDF_UNITPRICE'));
        $sText = $this->translate('ORDER_OVERVIEW_PDF_ALLPRICE');
        $oPdf->text(195 - $oPdf->getStringWidth($sText), $iTop, $sText);

        // separator line
        $iTop += 2;
        $oPdf->line(15, $iTop, 195, $iTop);

        // #345
        $siteH = $iTop;
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);

        // order articles
        $this->_setOrderArticlesToPdf($oPdf, $siteH, true);

        // generating pdf file
        if (strstr($this->oxorder__oxpaymenttype->value, 'klarna_')) {
            $oArtSumm = oxNew('klarna_invoicepdfarticlesummary', $this, $oPdf);
        } else {
            $oArtSumm = new InvoicepdfArticleSummary($this, $oPdf);
        }

        $iHeight = $oArtSumm->generate($siteH);
        if ($siteH + $iHeight > 258) {
            $this->pdfFooter($oPdf);
            $iTop = $this->pdfHeader($oPdf);
            $oArtSumm->ajustHeight($iTop - $siteH);
            $siteH = $iTop;
        }

        $oArtSumm->run($oPdf);
        $siteH += $iHeight + 8;

        $oPdf->text(15, $siteH, $this->translate('ORDER_OVERVIEW_PDF_GREETINGS'));
    }

    /**
     * Generating delivery note pdf.
     *
     * @param object $oPdf pdf document object
     * @throws oxSystemComponentException
     */
    public function exportDeliveryNote($oPdf)
    {
        $myConfig  = $this->getConfig();
        $oShop     = $this->_getActShop();
        $oPdfBlock = new InvoicepdfBlock();

        $oLang = oxRegistry::getLang();
        $sSal  = $this->oxorder__oxdelsal->value;
        try {
            $sSal = $oLang->translateString($this->oxorder__oxdelsal->value, $this->getSelectedLang());
        } catch (Exception $e) {
        }

        // loading order currency info
        $this->_oCur = $myConfig->getCurrencyObject($this->oxorder__oxcurrency->value);
        if (!isset($this->_oCur)) {
            $this->_oCur = $myConfig->getActShopCurrencyObject();
        }

        // shop info
        $oPdf->setFont($oPdfBlock->getFont(), '', 6);
        $oPdf->text(15, 55, $oShop->oxshops__oxname->getRawValue() . ' - ' . $oShop->oxshops__oxstreet->getRawValue() . ' - ' . $oShop->oxshops__oxzip->value . ' - ' . $oShop->oxshops__oxcity->getRawValue());

        // delivery address
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);
        if ($this->oxorder__oxdelsal->value) {
            $oPdf->text(15, 59, $sSal);
            $oPdf->text(15, 63, $this->oxorder__oxdellname->getRawValue() . ' ' . $this->oxorder__oxdelfname->getRawValue());
            $oPdf->text(15, 67, $this->oxorder__oxdelcompany->getRawValue());
            $oPdf->text(15, 71, $this->oxorder__oxdelstreet->getRawValue() . ' ' . $this->oxorder__oxdelstreetnr->value);
            $oPdf->setFont($oPdfBlock->getFont(), 'B', 10);
            $oPdf->text(15, 75, $this->oxorder__oxdelzip->value . ' ' . $this->oxorder__oxdelcity->getRawValue());
            $oPdf->setFont($oPdfBlock->getFont(), '', 10);
            $oPdf->text(15, 79, $this->oxorder__oxdelcountry->getRawValue());
        } else {
            // no delivery address - billing address is used for delivery
            $this->_setBillingAddressToPdf($oPdf);
        }

        // loading user info
        $oUser = oxNew('oxuser');
        $oUser->load($this->oxorder__oxuserid->value);

        // user info
        $sText = $this->translate('ORDER_OVERVIEW_PDF_FILLONPAYMENT');
        $oPdf->setFont($oPdfBlock->getFont(), '', 5);
        $oPdf->text(195 - $oPdf->getStringWidth($sText), 70, $sText);

        // customer number
        $sCustNr = $this->translate('ORDER_OVERVIEW_PDF_CUSTNR') . ' ' . $oUser->oxuser__oxcustnr->value;
        $oPdf->setFont($oPdfBlock->getFont(), '', 7);
        $oPdf->text(195 - $oPdf->getStringWidth($sCustNr), 73, $sCustNr);

        // shops city
        $sText = $oShop->oxshops__oxcity->getRawValue() . ', ' . date('d.m.Y');
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);
        $oPdf->text(195 - $oPdf->getStringWidth($sText), 95, $sText);

        $iTop = 99;
        // shop VAT number
        if ($oShop->oxshops__oxvatnumber->value) {
            $sText = $this->translate('ORDER_OVERVIEW_PDF_TAXIDNR') . ' ' . $oShop->oxshops__oxvatnumber->value;
            $oPdf->text(195 - $oPdf->getStringWidth($sText), $iTop, $sText);
            $iTop += 4;
        }

        // invoice number
        $sText = $this->translate('ORDER_OVERVIEW_PDF_COUNTNR') . ' ' . $this->oxorder__oxbillnr->value;
        $oPdf->text(195 - $oPdf->getStringWidth($sText), $iTop, $sText);

        // canceled order marker
        if ($this->oxorder__oxstorno->value == 1) {
            $this->oxorder__oxordernr->setValue($this->oxorder__oxordernr->getRawValue() . '   ' . $this->translate('ORDER_OVERVIEW_PDF_STORNO'), oxField::T_RAW);
        }

        // order number
        $oPdf->setFont($oPdfBlock->getFont(), '', 12);
        $oPdf->text(15, 108, $this->translate('ORDER_OVERVIEW_PDF_DELIVNOTE') . ' ' . $this->oxorder__oxordernr->value);

        // order date
        $aOrderDate = explode(' ', $this->oxorder__oxorderdate->value);
        $sOrderDate = oxRegistry::get("oxUtilsDate")->formatDBDate($aOrderDate[0]);
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);
        $oPdf->text(15, 119, $this->translate('ORDER_OVERVIEW_PDF_ORDERSFROM') . $sOrderDate . $this->translate('ORDER_OVERVIEW_PDF_ORDERSAT') . $oShop->oxshops__oxurl->value);

        // product info header
        $oPdf->setFont($oPdfBlock->getFont(), '', 8);
        $oPdf->text(15, 128, $this->translate('ORDER_OVERVIEW_PDF_AMOUNT'));
        $oPdf->text(30, 128, $this->translate('ORDER_OVERVIEW_PDF_ARTID'));
        $oPdf->text(45, 128, $this->translate('ORDER_OVERVIEW_PDF_DESC'));

        // line separator
        $oPdf->line(15, 130, 195, 130);

        // product list
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);
        $siteH = 130;

        // order articles
        $this->_setOrderArticlesToPdf($oPdf, $siteH, false);

        // sine separator
        $oPdf->line(15, $siteH + 2, 195, $siteH + 2);
        $siteH += 4;
    }

//    /**
//     * Get average of order VAT
//     *
//     * @return float
//     */
//    public function getOrderVatAverage()
//    {
//        $vatAvg = ($this->getTotalOrderSum() / $this->getOrderNetSum() - 1) * 100;
//
//        return number_format($vatAvg, 2);
//    }

    /**
     * @param $orderLang
     * @param bool $isCapture
     * @return mixed
     */
    public function getNewOrderLinesAndTotals($orderLang, $isCapture = false)
    {
        $cur = $this->getOrderCurrency();
        oxRegistry::getConfig()->setActShopCurrency($cur->id);
        if ($isCapture) {
            $this->reloadDiscount(false);
        }
//        $this->recalculateOrder();
        $oBasket = $this->_getOrderBasket();
        $oBasket->setKlarnaOrderLang($orderLang);
        $this->_addOrderArticlesToBasket($oBasket, $this->getOrderArticles(true));

        $oBasket->calculateBasket(true);
        $orderLines = $oBasket->getKlarnaOrderLines($this->getId());

        return $orderLines;
    }

    /**
     * @param oxException $e
     */
    public function showKlarnaErrorMessage(oxException $e)
    {
        if (in_array($e->getCode(), array(403, 422, 401, 404))) {
            $oLang = oxRegistry::getLang();

            return $oLang->translateString('KL_ORDER_UPDATE_CANT_BE_SENT_TO_KLARNA');
//            oxRegistry::get('oxUtilsView')->addErrorToDisplay(
//                $oLang->translateString('KL_ORDER_UPDATE_CANT_BE_SENT_TO_KLARNA'), false, true);
        }
    }

    /**
     * Set anonymous data if anonymization is enabled.
     *
     * @param $aArticleList
     */
    protected function _setOrderArticles($aArticleList)
    {

        parent::_setOrderArticles($aArticleList);

        if ($this->isKlarnaAnonymous()) {
            $oOrderArticles = $this->getOrderArticles();
            if ($oOrderArticles && count($oOrderArticles) > 0) {
                $this->_setOrderArticleKlarnaInfo($oOrderArticles);
            }
        }

    }

    /**
     * @param $oOrderArticles
     */
    protected function _setOrderArticleKlarnaInfo($oOrderArticles)
    {
        $iIndex = 0;
        foreach ($oOrderArticles as $oOrderArticle) {
//            if ($checkoutUtils->isKlarnaServiceProductId($oOrderArticle->getFieldData('oxartnum'))) {
//                continue;
//            }

            $iIndex++;
            $oOrderArticle->kl_setTitle($iIndex);
            $oOrderArticle->kl_setArtNum($iIndex);
        }
    }

    /**
     * @return mixed
     */
    protected function isKlarnaAnonymous()
    {
        if ($this->isAnonymous !== null)
            return $this->isAnonymous;

        return $this->isAnonymous = KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization');
    }
}