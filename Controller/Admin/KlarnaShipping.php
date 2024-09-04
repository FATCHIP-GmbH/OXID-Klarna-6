<?php


namespace TopConcepts\Klarna\Controller\Admin;


use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Model\KlarnaPayment;

class KlarnaShipping extends KlarnaBaseConfig
{
    const PICK_UP_STORE = 'PickUpStore';
    const HOME = 'Home';
    const BOX_REG = 'BoxReg';
    const BOX_UNREG = 'BoxUnreg';
    const PICK_UP_POINT = 'PickUpPoint';
    const OWN = 'Own';
    const POSTAL = 'Postal';
    const DHL_PACK_STATION = 'DHLPackstation';
    const DIGITAL = 'Digital';
    const POSTAL_WITH_DHL_PACK_STATION = 'Postal + DHLPackstation';    
    
    protected $_sThisTemplate = 'tcklarna_shipping.tpl';
    
    public function getKCOShippingMethods() {
        return array(
            self::POSTAL_WITH_DHL_PACK_STATION,
            self::PICK_UP_STORE,
            self::HOME,
            self::BOX_REG,
            self::BOX_UNREG,
            self::PICK_UP_POINT,
            self::OWN,
            self::POSTAL,
            self::DHL_PACK_STATION,
            self::DIGITAL
        );
    }
    
    
    public function render() {
        parent::render();
        $this->_aViewData += array(
            'KCOShippingSets' => $this->getKCOShippingSets(),
            'KCOShippingMethods' => $this->getKCOShippingMethods(),
        );
        
        return $this->_sThisTemplate;
    }

    public function getKCOShippingSets() {
        
        $list = Registry::get(DeliverySetList::class);
        $viewName = $list->getBaseObject()->getViewName();
        $kco = KlarnaPayment::KLARNA_PAYMENT_CHECKOUT_ID;
        
        $sql = "
            select 
                $viewName.*
            from
                $viewName
            join
                oxobject2payment o2p 
                on $viewName.oxid = o2p.oxobjectid
                and o2p.oxtype = 'oxdelset'
            where 
                " . $list->getBaseObject()->getSqlActiveSnippet() . "
            order by oxpos"
            
        ;
        $list->selectString($sql);
        
        return $list;
    }
}