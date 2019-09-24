<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 23.03.2018
 * Time: 10:50
 */

namespace TopConcepts\Klarna\Tests\Unit;

use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ConfigFile;

use OxidEsales\TestingLibrary\Services\Library\DatabaseHandler;
use OxidEsales\TestingLibrary\TestConfig;
use OxidEsales\TestingLibrary\UnitTestCase;

/** Load test helpers on top oxid core classes */
require_once __DIR__ . '/oxUtilsHelper.php';

class ModuleUnitTestCase extends UnitTestCase
{

    /** @var string */
    protected $moduleName;

    /** @var DatabaseHandler */
    protected $dbHandler;

    /** @var TestConfig  */
    protected $testConfig;

    protected function setUp()
    {
        parent::setUp();

        oxAddClassModule(\oxUtilsHelper::class, \OxidEsales\Eshop\Core\Utils::class);
    }


    /**
     * ModuleUnitTestCase constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     * @throws \Exception
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->moduleName = 'tcklarna';
        $this->testConfig = new TestConfig();
    }

    public function setUpBeforeTestSuite()
    {
        parent::setUpBeforeTestSuite();
        $configFile = Registry::get(ConfigFile::class);
        $this->dbHandler = new DatabaseHandler($configFile);
    }

    protected function removeQueryString($url)
    {
        $parsed = parse_url($url);
        if (isset($parsed['query']))
            return str_replace($parsed['query'], '', $url);

        return $url;
    }

    protected function tearDown()
    {
        parent::tearDown();

        \oxUtilsHelper::$sRedirectUrl = null;
        oxRemClassModule(\oxUtilsHelper::class, \OxidEsales\Eshop\Core\Utils::class);
    }

    protected function setupKlarnaExternals()
    {
        $config = [
            'oxidcashondel' => ['payment'],
            'oxidpayadvance' => ['checkout']
        ];

        foreach($config as $oxid => $values) {
            $oPayment = oxNew(Payment::class);
            $oPayment->load($oxid);

            if(in_array('payment', $values)) {
                $oPayment->oxpayments__tcklarna_externalpayment = new Field(1, Field::T_RAW);
            }

            if(in_array('checkout', $values)) {
                $oPayment->oxpayments__tcklarna_externalcheckout = new Field(1, Field::T_RAW);
            }

            $oPayment->save();
        }
    }

    public function setModuleMode($mode)
    {
        $this->getConfig()->saveShopConfVar('str', 'sKlarnaActiveMode', $mode, $this->getShopId(), 'module:tcklarna');
    }

    public function setModuleConfVar($name, $value, $type = 'str')
    {
        $this->getConfig()->saveShopConfVar($type, $name, $value, $this->getShopId(), 'module:tcklarna');
    }

//    /**
//     * @throws \Exception
//     */
//    public function insertOrderData()
//    {
//        $this->dbHandler->import($this->getModuleTestDataDir() . "insert_orders.sql");
//    }
//
//    /**
//     * @throws \Exception
//     */
//    public function insertArticle()
//    {
//        $this->dbHandler->import($this->getModuleTestDataDir() . "add_article_3102.sql");
//    }

    /**
     * @param $tableName
     * @throws \Exception
     */
    public function truncateTable($tableName)
    {
        $this->dbHandler->execSql("TRUNCATE $tableName");
    }

    /** Gets test path for current module */
    protected function getModuleTestDir()
    {
        foreach($this->testConfig->getPartialModulePaths() as $modulePartialPath){
            if(strpos($modulePartialPath, $this->moduleName)){
                return $this->testConfig->getShopPath() . 'modules/' . $modulePartialPath .'/Tests/';
            }
        }
    }

    protected function getModuleTestDataDir()
    {
        return $this->getModuleTestDir() . "Unit/Testdata/";
    }

    public function prepareBasketWithProduct()
    {

        $oBasket = oxNew('oxBasket');
//        $oBasket->setBasketUser($oUser);
        $this->setConfigParam('blAllowUnevenAmounts', true);
        $oBasket->addToBasket('adc5ee42bd3c37a27a488769d22ad9ed', 1);
        $oBasket->calculateBasket();

        //basket name in session will be "basket"
        $this->getConfig()->setConfigParam('blMallSharedBasket', 1);
        //$oSession->setBasket($oBasket);

        return $oBasket;
    }

    public function prepareKlarnaOrder()
    {
        $id = 'test_gen_' . rand(0, 100000);
        /** @var \OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database $db */
        $db = UnitTestCase::getDb();
        $orderColumns = '(`OXID`,`OXSHOPID`,`OXUSERID`,`OXORDERDATE`,`OXORDERNR`,`OXBILLCOMPANY`,`OXBILLEMAIL`,`OXBILLFNAME`,`OXBILLLNAME`,`OXBILLSTREET`,`OXBILLSTREETNR`,`OXBILLADDINFO`,`OXBILLUSTID`,`OXBILLCITY`,`OXBILLCOUNTRYID`,`OXBILLSTATEID`,`OXBILLZIP`,`OXBILLFON`,`OXBILLFAX`,`OXBILLSAL`,`OXDELCOMPANY`,`OXDELFNAME`,`OXDELLNAME`,`OXDELSTREET`,`OXDELSTREETNR`,`OXDELADDINFO`,`OXDELCITY`,`OXDELCOUNTRYID`,`OXDELSTATEID`,`OXDELZIP`,`OXDELFON`,`OXDELFAX`,`OXDELSAL`,`OXPAYMENTID`,`OXPAYMENTTYPE`,`OXTOTALNETSUM`,`OXTOTALBRUTSUM`,`OXTOTALORDERSUM`,`OXARTVAT1`,`OXARTVATPRICE1`,`OXARTVAT2`,`OXARTVATPRICE2`,`OXDELCOST`,`OXDELVAT`,`OXPAYCOST`,`OXPAYVAT`,`OXWRAPCOST`,`OXWRAPVAT`,`OXGIFTCARDCOST`,`OXGIFTCARDVAT`,`OXCARDID`,`OXCARDTEXT`,`OXDISCOUNT`,`OXEXPORT`,`OXBILLNR`,`OXBILLDATE`,`OXTRACKCODE`,`OXSENDDATE`,`OXREMARK`,`OXVOUCHERDISCOUNT`,`OXCURRENCY`,`OXCURRATE`,`OXFOLDER`,`OXTRANSID`,`OXPAYID`,`OXXID`,`OXPAID`,`OXSTORNO`,`OXIP`,`OXTRANSSTATUS`,`OXLANG`,`OXINVOICENR`,`OXDELTYPE`,`OXTIMESTAMP`,`OXISNETTOMODE`,`TCKLARNA_MERCHANTID`,`TCKLARNA_SERVERMODE`,`TCKLARNA_ORDERID`,`TCKLARNA_SYNC`)';
        $orderArticlesColumns = '(`OXID`,`OXORDERID`,`OXAMOUNT`,`OXARTID`,`OXARTNUM`,`OXTITLE`,`OXSHORTDESC`,`OXSELVARIANT`,`OXNETPRICE`,`OXBRUTPRICE`,`OXVATPRICE`,`OXVAT`,`OXPERSPARAM`,`OXPRICE`,`OXBPRICE`,`OXNPRICE`,`OXWRAPID`,`OXEXTURL`,`OXURLDESC`,`OXURLIMG`,`OXTHUMB`,`OXPIC1`,`OXPIC2`,`OXPIC3`,`OXPIC4`,`OXPIC5`,`OXWEIGHT`,`OXSTOCK`,`OXDELIVERY`,`OXINSERT`,`OXTIMESTAMP`,`OXLENGTH`,`OXWIDTH`,`OXHEIGHT`,`OXFILE`,`OXSEARCHKEYS`,`OXTEMPLATE`,`OXQUESTIONEMAIL`,`OXISSEARCH`,`OXFOLDER`,`OXSUBCLASS`,`OXSTORNO`,`OXORDERSHOPID`,`OXISBUNDLE`,`TCKLARNA_TITLE`,`TCKLARNA_ARTNUM`)';
        $db->execute("REPLACE INTO `oxorder` $orderColumns VALUES('$id', '1', 'bbf2387f1e85d75ffaac693c2338d400', '2018-03-13 11:45:41', '3', '', 'dabrowski@topconcepts.de', 'Greg', 'Dabrowski', 'afafafafafa', '1', '', '', 'Hamburg', 'a7c40f631fc920687.20179984', '', '12012', '', '', 'Mr', '', 'Greg', 'Dabrowski', 'afafafafafa', '1', '', 'Hamburg', 'a7c40f631fc920687.20179984', '', '12012', '', '', 'Mr', 'a66b77a68e3d3f84cd8950e7c99f5362', 'klarna_checkout', '276.47', '329', '329', '19', '52.53', '0', '0', '0', '19', '0', '0', '0', '0', '0', '19', '', '', '0', '0', '', '0000-00-00', '', '0000-00-00 00:00:00', '', '0', 'EUR', '1', 'ORDERFOLDER_NEW', '', '', '', '0000-00-00 00:00:00', '0', '', 'OK', '0', '0', 'oxidstandard', '2018-03-13 12:13:35', '0', 'K501664', '334d4946-6e76-7f13-9b78-c4461b5c8b9d', '1', '')");
        $db->execute("REPLACE INTO `oxorderarticles` $orderArticlesColumns VALUES('3c492e4ed3b7aa51b0fd1d85e26d2dc7', '$id', '0', '058de8224773a1d5fd54d523f0c823e0', '1302', 'Kiteboard CABRINHA CALIBER 2011', 'Freestyle und Freeride Board', '', '402.52', '479', '76.48', '19', '', '479', '479', '402.52', '', '', '', '', '', 'cabrinha_caliber_2011.jpg', 'cabrinha_caliber_2011_deck.jpg', 'cabrinha_caliber_2011_bottom.jpg', '', '', '0', '12', '0000-00-00', '2010-12-06', '2018-03-13 12:13:35', '0', '0', '0', '', 'kiteboard, kite, board, caliber, cabrinha', '', '', '1', '', 'oxarticle', '0', '1', '0', '', '')");
        $db->execute("REPLACE INTO `oxorderarticles` $orderArticlesColumns VALUES('886fab2af7827129caa39ef0be3e522e', '$id', '1', 'ed6573c0259d6a6fb641d106dcb2faec', '2103', 'Wakeboard LIQUID FORCE GROOVE 2010', 'Stylisches Wakeboard mit traumhafter Performance', '', '276.47', '329', '52.53', '19', '', '329', '329', '276.47', '', '', '', '', '', 'lf_groove_2010_1.jpg', 'lf_groove_2010_deck_1.jpg', 'lf_groove_2010_bottom_1.jpg', '', '', '0', '9', '0000-00-00', '2010-12-09', '2018-03-13 11:45:41', '0', '0', '0', '', 'wakeboarding, wake, board, liquid force, groove', '', '', '1', '', 'oxarticle', '0', '1', '0', '', '')");

        return $id;
    }

    public function removeKlarnaOrder($id)
    {
        /** @var \OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database $db */
        $db = UnitTestCase::getDb();
        $db->execute("DELETE FROM `oxorder` WHERE `oxid` = '$id'");
        $db->execute("DELETE FROM `oxorderarticles` WHERE `oxorderid` = '$id'");
    }

}