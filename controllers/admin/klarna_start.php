<?php
/**
 * Copyright 2015 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Class Klarna_Config for module configuration in OXID backend
 */
class Klarna_Start extends klarna_base_config
{

    protected $_sThisTemplate = 'kl_klarna_start.tpl';

    /**
     * Render logic
     *
     * @see admin/oxAdminDetails::render()
     * @return string
     */
    public function render()
    {
        // force shopid as parameter
        // Pass shop OXID so that shop object could be loaded
        $sShopOXID = oxRegistry::getConfig()->getShopId();

        $this->setEditObjectId($sShopOXID);

        parent::render();

        return $this->_sThisTemplate;
    }

    /**
     * @return string
     * @throws oxSystemComponentException
     */
    public function getKlarnaModuleInfo()
    {
        $module = oxNew('oxModule');
        $module->load('klarna');

        $description = strtoupper($module->getInfo('description'));
        $version     = $module->getInfo('version');

        return $description . " VERSION " . $version;
    }
}