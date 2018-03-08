<?php

class Klarna_Pass_Through
{
    /**
     * To be implemented by the merchant
     * @return array
     */
    public function getPassThroughField()
    {
        return array();
    }
}