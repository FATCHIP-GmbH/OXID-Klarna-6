<?php

namespace TopConcepts\Klarna\Models\EmdPayload;


class KlarnaPassThrough
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