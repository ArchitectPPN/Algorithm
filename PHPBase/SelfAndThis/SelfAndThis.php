<?php

#Self和this/static有什么区别
class FatherObj
{

}

class SelfAndThis extends FatherObj
{

}

class SonObj
{

}

#使用 self:: 或者 __CLASS__ 对当前类的静态引用，取决于定义当前方法所在的类：
class A
{
    public static function who()
    {
        echo __CLASS__ . PHP_EOL;
    }

    public static function test()
    {
        self::who();
        static::who();
    }

    public function echoClass()
    {
        echo __CLASS__;
    }
}

class B extends A
{
    public static function who()
    {
        echo __CLASS__;
    }

    public function echoClass()
    {
        echo __CLASS__;
    }
}

B::test();

//(new B())->echoClass();

