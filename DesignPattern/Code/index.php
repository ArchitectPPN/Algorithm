<?php

//自动加载
function autoLoad(string $className)
{
    $fileName = $className . '.php';
    if (is_file($fileName)) {
        require_once $fileName;
    }
}

spl_autoload_register('autoLoad');

(new \DesignPattern\Application())->run();



