<?php

class klarna_basket extends klarna_basket_parent
{
    /**
     * Rendering template
     *
     * @return mixed
     */
    public function render()
    {
        if (oxRegistry::getConfig()->getRequestParameter('error')) {
            oxRegistry::get("oxUtilsView")->addErrorToDisplay('KL_EXCEPTION_OUT_OF_STOCK', false, true);
        }

        $this->getErrorsFromQueryString();

        if(oxRegistry::getConfig()->getRequestParameter('openAmazonLogin')){
            $this->addTplParam('openAmazonLogin', true);
        }

        return parent::render();
    }

    /**
     *
     */
    public function getErrorsFromQueryString()
    {
        parse_str($_SERVER['QUERY_STRING'], $urlVars);
        foreach ($urlVars as $key => $value) {
            if (strstr($key, 'error_msg')) {
                oxRegistry::get("oxUtilsView")->addErrorToDisplay($value);
            }
        }
    }
}