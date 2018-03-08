<?php

namespace Klarna\Klarna\Controllers\Admin;

use Klarna\Klarna\Core\KlarnaConsts;
use Klarna\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database;
use OxidEsales\Eshop\Core\DatabaseProvider as oxDb;
use OxidEsales\Eshop\Core\Registry as oxRegistry;
use OxidEsales\Eshop\Core\Request;

class KlarnaBaseConfig extends ShopConfiguration
{
    /**
     * Request parameter container
     *
     * @var array
     */
    protected $_aParameters = array();

    /**
     * @var Request
     */
    protected $_oRequest;

    /**
     * Keys with these prefixes will be deleted on save operation if not present in the POST array
     * Assign prefixes in final controller class
     * @var array
     */
    protected $_collectionsPrefixes = array();

    /** @var array klarna multilang config vars */
    protected $MLVars = array();

    /**
     * Sets parameter
     *
     * @param $sName
     * @param $sValue
     */
    public function setParameter($sName, $sValue)
    {
        $this->_aParameters[$sName] = $sValue;
    }

    /**
     * Return parameter from container
     *
     * @param $sName
     * @return string
     */
    public function getParameter($sName)
    {
        return $this->_aParameters[$sName];
    }

    public function init()
    {
        parent::init();
        $this->_oRequest = oxRegistry::get(Request::class);
    }

    public function render()
    {
        parent::render();
        foreach ($this->_aViewData['confaarrs'] as $key => $arr) {
            $this->_aViewData['confaarrs'][$key] = $this->_multilineToAarray(html_entity_decode($arr));
        }
    }

    /**
     * Save configuration values
     *
     * @return void
     * @throws oxSystemComponentException
     */
    public function save()
    {
        // Save parameters to container
        $this->fillContainer();
        $this->doSave();
    }

    /**
     * Fill parameter container with request values
     */
    protected function fillContainer()
    {
        foreach ($this->_aConfParams as $sType => $sParam) {

            if ($sType === 'aarr') {
                $this->setParameter($sParam,
                    $this->convertNestedParams(
                        $this->_oRequest->getRequestEscapedParameter($sParam)
                    ));
            } else {
                $this->setParameter($sParam, $this->_oRequest->getRequestEscapedParameter($sParam));
            }
        }
    }

    /**
     * @param $nestedArray
     * @return array
     */
    protected function convertNestedParams($nestedArray)
    {
        if (is_array($nestedArray)) {
            foreach ($nestedArray as $key => $arr) {
                /*** serialize all assoc arrays ***/
                $nestedArray[$key] = $this->_aarrayToMultiline($arr);
            }
        }

        return $nestedArray;
    }

    /**
     * @param $aKeys
     * @return int
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function removeConfigKeys($aKeys)
    {
        /** @var Database $db */
        $db = oxDb::getDb(oxDb::FETCH_MODE_ASSOC);

        $sql = "DELETE 
                    FROM oxconfig
                WHERE oxvarname IN ('" . implode("','", $aKeys) . "')
                    AND oxshopid = '{$this->getConfig()->getShopId()}'";

        return $db->execute($sql);
    }

    /**
     * Save vars as shop config does
     * @throws \Exception
     */
    private function doSave()
    {
        $this->performConfVarsSave();
        $sOxid = $this->getEditObjectId();

        //saving additional fields ("oxshops__oxdefcat") that goes directly to shop (not config)
        /** @var Shop $oShop */
        $oShop = oxNew(Shop::class);
        if ($oShop->load($sOxid)) {
            $oShop->assign($this->_oRequest->getRequestEscapedParameter("editval"));
            $oShop->save();
        }
    }

    /**
     * Shop config variable saving
     */
    private function performConfVarsSave()
    {
        $this->resetContentCache();
        foreach ($this->_aConfParams as $sType => $sParam) {
            $aConfVars = $this->getParameter($sParam);

            if (!is_array($aConfVars)) {
                continue;
            }

            $this->_performConfVarsSave($sType, $aConfVars);
        }
    }

    /**
     * Save config parameter
     *
     * @param $sConfigType
     * @param $aConfVars
     */
    protected function _performConfVarsSave($sConfigType, $aConfVars)
    {
        $myConfig = oxRegistry::getConfig();
        $sShopId  = $this->getEditObjectId();
        $sModule  = $this->_getModuleForConfigVars();

        foreach ($aConfVars as $sName => $sValue) {
            $oldValue = $myConfig->getConfigParam($sName);
            if ($sValue !== $oldValue) {
                $myConfig->saveShopConfVar(
                    $sConfigType,
                    $sName,
                    $this->_serializeConfVar($sConfigType, $sName, $sValue),
                    $sShopId,
                    $sModule
                );
            }
        }
    }

    /**
     * @return string
     */
    protected function _getModuleForConfigVars()
    {
        return 'klarna';
    }

    /**
     * @throws oxConnectionException
     */
    public function getAllActiveOxPaymentIds()
    {
        $db = oxdb::getDb(oxdb::FETCH_MODE_ASSOC);

        $sql    = 'SELECT oxid FROM oxpayments WHERE oxactive=1 AND oxid != "oxempty"';

        $result = $db->select($sql);

        return $result;
    }

    /**
     * @param string $oxid
     * @param bool|int $lang
     * @return mixed
     * @throws oxSystemComponentException
     */
    public function getPaymentData($oxid, $lang = false)
    {
        $lang      = $lang !== false ? $lang : $this->_aViewData['adminlang'];
        $oxpayment = oxnew('oxPayment');
        $oxpayment->loadInLang($lang, $oxid);

        $result['oxid'] = $oxid;
        $result['desc'] = $oxpayment->oxpayments__oxdesc->value;
//        $result['klpaymenttypes']           = $oxpayment->oxpayments__klpaymenttypes->value;
        $result['klexternalname']           = $oxpayment->oxpayments__klexternalname->value;
        $result['klexternalpayment']        = $oxpayment->oxpayments__klexternalpayment->value;
        $result['klexternalcheckout']       = $oxpayment->oxpayments__klexternalcheckout->value;
        $result['klpaymentimageurl']        = $oxpayment->oxpayments__klpaymentimageurl->value;
        $result['klcheckoutimageurl']       = $oxpayment->oxpayments__klcheckoutimageurl->value;
        $result['klpaymentoption']          = $oxpayment->oxpayments__klpaymentoption->value;
        $result['klemdpurchasehistoryfull'] = $oxpayment->oxpayments__klemdpurchasehistoryfull->value;
        $result['isCheckout']               = preg_match('/([pP]ay[pP]al|[Aa]mazon)/', $result['desc']) == 1;
        $result['isExternalEnabled']        = $result['klexternalpayment'] == 1 || $result['klexternalcheckout'] == 1;

        return $result;
    }

    /**
     * @return string
     */
    protected function getActiveKlarnaMode()
    {
        return KlarnaUtils::getShopConfVar('sKlarnaActiveMode');
    }


    /**
     * @return string
     */
    public function getManualDownloadLink()
    {
        $langTag = oxRegistry::getLang()->getLanguageAbbr($this->_aViewData['adminlang']);

        return sprintf(KlarnaConsts::KLARNA_MANUAL_DOWNLOAD_LINK, $langTag);
    }

    public function getLangs()
    {
        return htmlentities(json_encode(
            oxRegistry::getLang()->getLanguageArray()
        ));
    }

    public function getFlippedLangArray()
    {
        $aLangs = oxRegistry::getLang()->getLanguageArray();

        $return = array();
        foreach ($aLangs as $oLang) {
            $return[$oLang->abbr] = $oLang;
        }

        return $return;
    }

    protected function getMultiLangData()
    {
        $output = array();

        foreach ($this->MLVars as $fieldName) {
            foreach ($this->_aViewData['confstrs'] as $name => $value) {
                if (strpos($name, $fieldName) === 0) {
                    $output['confstrs[' . $name . ']'] = $value;
                }
            }
        }

        return $output;
    }

}