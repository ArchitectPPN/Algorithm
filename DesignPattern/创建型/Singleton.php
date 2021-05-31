<?php


class Singleton
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * 构造函数私有化
     * Singleton constructor.
     */
    private function __construct(){}

    /**
     * 防止对象被克隆
     */
    private function __clone(){}

    /**
     * 防止被反序列化
     */
    private function __wakeup(){}
}