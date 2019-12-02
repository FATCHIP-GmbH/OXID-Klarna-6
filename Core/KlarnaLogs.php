<?php
/**
 * Copyright 2018 Klarna AB
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

namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Core\Model\BaseModel;

/**
 * Klarna model class for table 'tcklarna_logs'
 */
class KlarnaLogs extends BaseModel
{
    protected $validObjectIds = [
        'order_id',
//        'authorization_token'
    ];

    /**
     * Class constructor, initiates parent constructor.
     * @codeCoverageIgnore
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('tcklarna_logs');
    }

    /**
     * @throws \Exception
     * @return bool|string
     */
    public function save()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaLoggingEnabled')) {
            return parent::save();
        }
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function logData($action, $requestBody, $url, $object_id, $response, $statusCode, $mid = '')
    {
        if ($object_id === null) {
            $object_id = $this->resolveObjectId($response);
        }

        if (is_array($response)) {
            $response = json_encode($response);
        }

        if (is_array($requestBody)) {
            $requestBody = json_encode($requestBody);
        }

        $aData      = array(
            'tcklarna_logs__tcklarna_method'      => $action,
            'tcklarna_logs__tcklarna_url'         => $url,
            'tcklarna_logs__tcklarna_orderid'     => $object_id,
            'tcklarna_logs__tcklarna_mid'         => $mid,
            'tcklarna_logs__tcklarna_statuscode'  => $statusCode,
            'tcklarna_logs__tcklarna_requestraw'  => $requestBody,
            'tcklarna_logs__tcklarna_responseraw' => $response,
            'tcklarna_logs__tcklarna_date'        => date("Y-m-d H:i:s"),
        );
        $this->assign($aData);
        $this->save();
    }

    /**
     * @codeCoverageIgnore
     */
    protected function resolveObjectId($data) {
        if (is_string($data)) {
            $data = (array)json_decode($data, true);
        }
        foreach($this->validObjectIds as $key) {
            if (isset($data[$key])) {
                return $data[$key];
            }
        }
        return '';
    }
}