<?php

namespace DesignPattern;

abstract class BaseObject
{
    protected $log = 'log';

    protected $db = null;

    public function __construct()
    {
        $this->log = 'Log Record!' . PHP_EOL;
        $this->db = 'Db!' . PHP_EOL;
    }

    /**
     * run
     *
     * @param array $requestParams
     * @param string $requestMethod
     * @return false|string
     */
    final public function run(array $requestParams, string $requestMethod)
    {
        echo "log::request_method:{$requestMethod}. request_params:" . json_encode($requestParams, JSON_UNESCAPED_UNICODE) . PHP_EOL;

        $returnRes = $this->business($requestParams);

        echo "log::request_method:{$requestMethod}. return_res:" . json_encode($returnRes, JSON_UNESCAPED_UNICODE) . PHP_EOL;

        return json_encode($returnRes, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 业务逻辑入口
     *
     * @param array $requestParams
     * @return mixed
     */
    abstract protected function business(array $requestParams);
}