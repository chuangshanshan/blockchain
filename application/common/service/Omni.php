<?php


namespace app\common\service;


use fast\Http;

class Omni
{
    protected $rpcUrl = '';
    protected $rpcUserName = '';
    protected $rpcPassword = '';
    public static $instance = null;

    protected function __construct()
    {
    }

    public static function getInstance()
    {
        return self::$instance ?: self::$instance = new self();
    }

    public function send($fromaddress, $toaddress, $amount)
    {
        $method = 'omni_send';
        $propertyid = 31;//USDT
        $params = compact('method', 'fromaddress', 'toaddress', 'propertyid', 'amount');
        $this->request($params);
    }

    public function getBalance($address)
    {
        $method = 'omni_getbalance';
        $propertyid = 31;//USDT
        $params = compact('method', 'address', 'propertyid');
        $this->request($params);
    }

    public function request($params)
    {
        $jsonParams = json_encode($params);
        $options = [
            CURLOPT_HEADER => [
                'Authorization: Basic ' . base64_encode($this->rpcUserName . ':' . $this->rpcPassword),
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonParams)
            ]
        ];
        return Http::post($this->rpcUrl, $jsonParams, $options);
    }
}