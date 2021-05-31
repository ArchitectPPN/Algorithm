<?php


class Camera
{
    public function turnOn()
    {
        echo "打开录像机" . PHP_EOL;
    }

    public function turnOff()
    {
        echo "关闭录像机" . PHP_EOL;
    }
}

class Light
{
    public function turnOn()
    {
        echo "开灯" . PHP_EOL;
    }

    public function turnOff()
    {
        echo "关灯" . PHP_EOL;
    }

    public function changeBulb()
    {
        echo "换灯泡" . PHP_EOL;
    }
}

class Sensor
{
    public function activate()
    {
        echo "启动感应器" . PHP_EOL;
    }

    public function deactivate()
    {
        echo "关闭感应器" . PHP_EOL;
    }

    public function trigger()
    {
        echo "触发感应器" . PHP_EOL;
    }
}

class Alarm
{
    public function activate()
    {
        echo "启动警报器" . PHP_EOL;
    }

    public function deactivate()
    {
        echo "关闭警报器" . PHP_EOL;
    }

    public function ring()
    {
        echo "拉响警报器" . PHP_EOL;
    }

    public function stopRing()
    {
        echo "停掉警报器" . PHP_EOL;
    }
}

class Facade
{
    private $camera;

    private $light;

    private $sensor;

    private $alarm;

    public function __construct()
    {
        $this->camera = new Camera();
        $this->alarm  = new Alarm();
        $this->light  = new Light();
        $this->sensor = new Sensor();
    }

    /**
     * 启动接口
     */
    public function activate()
    {
        $this->camera->turnOn();
        $this->light->turnOn();
        $this->sensor->activate();
        $this->alarm->activate();
    }

    /**
     * 关闭接口
     */
    public function deactivate()
    {
        $this->camera->turnOff();
        $this->light->turnOff();
        $this->sensor->deactivate();
        $this->alarm->deactivate();
    }

    /**
     * 其他功能
     */
    public function otherFunction()
    {
        $this->light->changeBulb();
        $this->sensor->trigger();
        $this->alarm->ring();
        $this->alarm->stopRing();
    }
}

class Client
{
    private static $security;

    public static function main()
    {
        self::$security = new Facade();

        self::$security->activate();
        self::$security->deactivate();
        self::$security->otherFunction();
    }
}

Client::main();