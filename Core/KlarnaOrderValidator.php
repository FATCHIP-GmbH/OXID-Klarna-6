<?php

class KlarnaOrderValidator extends oxBase
{
    protected $aOrderData;

    /**
     * Reference prefix exclude from array
     *
     * @var string
     */
    protected $_sReferencePrefix = "SRV_";

    /**
     * Errors might occur when validating
     *
     * @var array
     */
    protected $_aResultErrors = array();

    /**
     * @var boolean
     */
    protected $_bResult;

    /**
     * @return boolean
     */
    public function getResult()
    {
        return $this->_bResult;
    }

    /**
     * @return array
     */
    public function getResultErrors()
    {
        return $this->_aResultErrors;
    }

    /**
     * KlarnaOrderValidator constructor.
     * @param array $aOrderData from Klarna validation request
     */
    public function __construct($aOrderData)
    {
        parent::__construct();
        $this->aOrderData = $aOrderData;
    }

    /**
     * @return bool
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    public function validateOrder()
    {
        $aOrderItems = $this->_fetchOrderItems();
        if (empty($aOrderItems)) {
            return false;
        }

        $this->_validateItemsBuyable($aOrderItems);

        return count($this->_aResultErrors) === 0 ? true : false;
    }

    /**
     * Returning order articles list
     *
     * @return int|mixed
     */
    protected function _fetchOrderItems()
    {
        // remove services from articles list
        foreach ($this->aOrderData['order_lines'] as $index => $aItem) {
            if (strstr($aItem['reference'], $this->_sReferencePrefix)) {
                unset($this->aOrderData['order_lines'][$index]);
            }
        }

        return $this->aOrderData['order_lines'];
    }

    /**
     * Validating if product items buyable and with enough stock
     *
     * @param array $aItems
     * @return void
     * @throws oxSystemComponentException
     * @throws oxConnectionException
     */
    protected function _validateItemsBuyable($aItems)
    {
        $mergedProducts = array();
        foreach ($aItems as $item) {
            if (!isset($mergedProducts[$item['reference']])) {
                $mergedProducts[$item['reference']] = 0;
            }
            $mergedProducts[$item['reference']] += $item['quantity'];
        }
        $this->_validateOxidProductsBuyable($mergedProducts);
    }

    /**
     * Check if provided products with requested amount are buyable
     *
     * @param $mergedProducts
     * @throws oxSystemComponentException
     * @throws oxConnectionException
     */
    protected function _validateOxidProductsBuyable($mergedProducts)
    {
        $oArticleObject = oxNew('oxarticle');
        $oLang          = oxRegistry::getLang();

        foreach ($mergedProducts as $itemKey => $itemAmount) {
            $oArticleObject->klarna_loadByArtNum($itemKey);

            if (!$oArticleObject->isLoaded()) {
                $this->_aResultErrors[] =
                    sprintf(
                        $oLang->translateString(
                            'ERROR_MESSAGE_ARTICLE_ARTICLE_DOES_NOT_EXIST',
                            $oLang->getBaseLanguage()
                        ),
                        $itemKey
                    );

                return;
            }

            if (!$oArticleObject->isBuyable()) {
                $this->_aResultErrors[] =
                    sprintf(
                        $oLang->translateString(
                            'ERROR_MESSAGE_ARTICLE_ARTICLE_NOT_BUYABLE',
                            $oLang->getBaseLanguage()
                        ),
                        $itemKey
                    );

                return;
            }

            if ($oArticleObject->checkForStock($itemAmount) !== true) {
                $this->_aResultErrors[] = sprintf($oLang->translateString(
                    'KL_ERROR_NOT_ENOUGH_IN_STOCK', $oLang->getBaseLanguage()),
                    $itemKey);

                return;
            }
        }
    }

}