<?php
namespace Klarna\Klarna\Models;
use Klarna\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class Klarna_oxArticle extends oxArticle class
 */
class KlarnaArticle extends KlarnaArticle_parent
{
    /**
     * Array of Klarna_PClass objects
     * @var array
     */
    protected $_aPClassList = null;

    /**
     * Show monthly cost?
     * @var bool
     */
    protected $_blShowMonthlyCost = null;

    /**
     * Check whether show monthly cost or not
     *
     * @return bool
     */
    public function showMonthlyCost()
    {
        if (defined('OXID_PHP_UNIT')) {
            $this->_blShowMonthlyCost = null;
        }

        // Check if Klarna Part payment enabled at all
        if (!$this->getKarnaPartEnabled()) {
            $this->_blShowMonthlyCost = false;
        }

        if ($this->_blShowMonthlyCost === null) {
            $this->_checkMonthlyCostDisplayConditionsAccepted();
        }

        return $this->_blShowMonthlyCost;
    }

    /**
     * Check preorder status
     *
     * @return boolean
     */
    protected function _checkPreorderStatus()
    {
        if (Registry::getConfig()->getConfigParam('iKlarnaMaxDaysForPreorder')) {
            $iInDays = (int)Registry::getConfig()->getConfigParam('iKlarnaMaxDaysForPreorder');
            if ($this->willNotExpired($iInDays)) {
                return $this->_blShowMonthlyCost = false;
            }
        }

        return true;
    }

    /**
     * Check whether to show cost in respective view
     * @return boolean
     */
    protected function _checkShowCost()
    {
        $blReturn = true;
        $oView    = Registry::getConfig()->getActiveView();
        $sCurView = $oView->getClassName();

        // if OXID version < 4.7.0
        if (version_compare(Registry::getConfig()->getVersion(), '4.7.0') == -1) {
            $navigationParam = '';
        } else {
            $navigationParam = $oView->getViewParameter('_navurlparams');
        }

        if ($this->_isDetailsPage($sCurView)) {
            $blReturn = $this->_checkShowCostInDetails();
        } elseif ($this->_isListPage($sCurView, $navigationParam)) {
            $blReturn = $this->_checkShowCostInList();
        } elseif ($this->_isStartPage($sCurView, $navigationParam)) {
            $blReturn = $this->_checkShowCostInStart();
        }

        return $blReturn;
    }

    /**
     * Check if product is displayed in details page
     *
     * @param $sCurView
     * @return bool
     */
    protected function _isDetailsPage($sCurView)
    {
        return in_array($sCurView, array('details', 'oxwarticledetails'));
    }

    /**
     * Check if product is displayed in list/serach page
     *
     * @param $sCurView
     * @param $navigationParam
     * @return bool
     */
    protected function _isListPage($sCurView, $navigationParam)
    {
        return (
            in_array($sCurView, array('alist', 'search'))
            || stripos($navigationParam, '=alist') !== false
            || stripos($navigationParam, '=search') !== false
        );
    }

    /**
     * Check if product is displayed in start page
     *
     * @param $sCurView
     * @param $navigationParam
     * @return bool
     */
    protected function _isStartPage($sCurView, $navigationParam)
    {
        return (
            in_array($sCurView, array('start'))
            || stripos($navigationParam, '=start') !== false
        );
    }

    /**
     * @return bool
     */
    protected function _checkShowCostInDetails()
    {
        if (Registry::getConfig()->getConfigParam('blKlarnaShowMonthlyRtsDetails')) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function _checkShowCostInList()
    {
        if (Registry::getConfig()->getConfigParam('blKlarnaShowMonthlyRtsList')) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function _checkShowCostInStart()
    {
        if (Registry::getConfig()->getConfigParam('blKlarnaShowMonthlyRtsStart')) {
            return true;
        }

        return false;
    }

    /**
     * Check minimum amout is ok
     *
     * @param string $sCountry
     * @return boolean
     */
    protected function _checkMinimumAmount($sCountry)
    {
        $dCurrentMinAmount = Registry::getConfig()->getConfigParam('iKlarnaMonthlyRateMinAmount' . $sCountry);
        if ($dCurrentMinAmount && ((double)$dCurrentMinAmount > $this->getPrice()->getBruttoPrice())) {
            return false;
        }

        return true;
    }

    /**
     * Check if article stock is good for expire check
     *
     * @return bool
     */
    public function isGoodStockForExpireCheck()
    {
        return (
            $this->getFieldData('oxstock') == 0
            && ($this->getFieldData('oxstockflag') == 1 || $this->getFieldData('oxstockflag') == 4)
        );
    }


    /**
     * Returning stock items by article number
     *
     * @param $sArtNum
     * @return object oxarticle
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function klarna_loadByArtNum($sArtNum)
    {
        $sArticleTable = $this->getViewName();
        if (strlen($sArtNum) === 64) {
            $sArtNum   .= '%';
            $sSQL      = "SELECT art.oxid FROM {$sArticleTable} art WHERE art.OXACTIVE=1 AND art.OXARTNUM LIKE \"{$sArtNum}\"";
            $articleId = DatabaseProvider::getDb(ADODB_FETCH_ASSOC)->getOne($sSQL);
        } else {
            if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization')) {
                $sSQL = "SELECT oxartid 
                            FROM kl_anon_lookup 
                            JOIN {$sArticleTable} art
                            ON art.OXID=oxartid
                            WHERE art.OXACTIVE=1 AND klartnum = ?";
            } else {
                $sSQL = "SELECT art.oxid FROM {$sArticleTable} art WHERE art.OXACTIVE=1 AND art.OXARTNUM = ?";
            }
            $articleId = DatabaseProvider::getDb(ADODB_FETCH_ASSOC)->getOne($sSQL, array($sArtNum));
        }

        return $this->load($articleId);
    }


    /**
     * Return anonymized or regular product title
     *
     * @param null $counter
     * @param null $iOrderLang
     * @return mixed
     * @throws oxSystemComponentException
     */
    public function kl_getOrderArticleName($counter = null, $iOrderLang = null)
    {

        if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization')) {
            if ($iOrderLang) {
                $lang = strtoupper(Registry::getLang()->getLanguageAbbr($iOrderLang));
            } else {
                $lang = strtoupper(Registry::getLang()->getLanguageAbbr());
            }

            $name = KlarnaUtils::getShopConfVar('sKlarnaAnonymizedProductTitle_' . $lang);

            return html_entity_decode("$name $counter", ENT_QUOTES);
        }

        $name = null;
        $name = $this->getFieldData('oxtitle');

        if (!$name && $parent = $this->getParentArticle()) {
            if ($iOrderLang) {
                $this->loadInLang($iOrderLang, $parent->getId());
            } else {
                $this->load($parent->getId());
            }
            $name = $this->getFieldData('oxtitle');
        }

        return html_entity_decode($name, ENT_QUOTES) ?: '(no title)';
    }

    /**
     * @return array
     */
    public function kl_getArticleUrl()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaSendProductUrls') === true &&
            KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {

            $link = $this->getLink(null, true);

            $link = preg_replace('/\?.+/', '', $link);

            return $link ?: null;
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function kl_getArticleImageUrl()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaSendImageUrls') === true &&
            KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {

            $link = $this->getPictureUrl();
        }

        return $link ?: null;
    }

    /**
     * @return null
     */
    public function kl_getArticleEAN()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {
            $ean = $this->getFieldData('oxean');
        }

        return $ean ?: null;
    }

    /**
     * @return null
     */
    public function kl_getArticleMPN()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {
            $mpn = $this->getFieldData('oxmpn');
        }

        return $mpn ?: null;
    }

    /**
     * @return string
     */
    public function kl_getArticleCategoryPath()
    {
        $sCategories = null;
        if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {
            $oCat = $this->getCategory();

            if ($oCat) {
                $aCategories = KlarnaUtils::getSubCategoriesArray($oCat);
                $sCategories = html_entity_decode(implode(' > ', array_reverse($aCategories)), ENT_QUOTES);
            }

        }

        return $sCategories;
    }

    /**
     * @return string|null
     */
    public function kl_getArticleManufacturer()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {
            if (!$oManufacturer = $this->getManufacturer())
                return null;
        }

        return html_entity_decode($oManufacturer->getTitle(), ENT_QUOTES) ?: null;
    }

}
