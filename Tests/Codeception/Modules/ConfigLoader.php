<?php

namespace TopConcepts\Klarna\Tests\Codeception\Modules;

use Codeception\Module;
use Codeception\TestInterface;

class ConfigLoader extends Module
{
    /**
     * @param TestInterface $test
     * @throws \Exception
     */
    public function _before(TestInterface $test)
    {
        echo "start";


        echo "finish";
    }

}