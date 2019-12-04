<?php


namespace TopConcepts\Klarna\Core;


class Config extends Config_parent
{
    protected function _setDefaults()
    {
        parent::_setDefaults();
        $sessionStartRules = $this->getConfigParam('aRequireSessionWithParams');
        $sessionStartRules['fnc']['startSessionAjax'] = true;
        $this->setConfigParam('aRequireSessionWithParams', $sessionStartRules);
    }
}