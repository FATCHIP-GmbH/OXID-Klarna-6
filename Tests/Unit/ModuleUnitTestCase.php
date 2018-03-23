<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 23.03.2018
 * Time: 10:50
 */

namespace TopConcepts\Klarna\Tests\Unit;

use OxidEsales\TestingLibrary\UnitTestCase;
use TopConcepts\Klarna\Core\KlarnaUtils;


class ModuleUnitTestCase extends UnitTestCase
{
    protected function setUp()
    {
        parent::setUp();

        require_once TEST_LIBRARY_HELPERS_PATH . 'oxUtilsHelper.php';
        oxAddClassModule(\oxUtilsHelper::class, "oxutils");
    }
}