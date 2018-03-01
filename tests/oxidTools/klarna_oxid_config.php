<?php

class KlarnaOxidConfig extends shop_config
{
    const KLARNA_MODULE_ID = 'klarna';

    /**
     * @var oxConfig
     *
     */
    protected $oConfig;

    /**
     * @var string
     */
    protected $shopId;

    public function __construct()
    {
        parent::__construct();
        $this->oConfig = oxRegistry::getConfig();
        $this->shopId = $this->oConfig->getShopId();
    }

    public function setShopConfig(array $configData)
    {
        foreach ($configData as $type => $values) {
            foreach ($values as $name => $data) {

                if(!in_array($type, array('str', 'bool', 'arr', 'aarr'))) {
                    print_r("Unsupported type $type\n");
                    break;
                }

                if ($type === 'aarr') {
                    $data = html_entity_decode($data);
                }

                $this->oConfig->saveShopConfVar(
                    $type,
                    $name,
                    $this->_serializeConfVar($type, $name, $data),
                    $this->shopId,
                    self::KLARNA_MODULE_ID
                );
            }
        }
    }
}