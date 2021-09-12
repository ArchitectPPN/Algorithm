<?php


class A
{
    public function echoClass()
    {
        echo __CLASS__;
    }

    public function echoA()
    {
        echo 'A';
    }

    public function test()
    {
        self::echoA();
        $this->echoA();
        static::echoA();
    }
}

class B extends A
{
    public function echoClass()
    {
        echo __CLASS__;
    }

    public function echoA()
    {
        echo 'B';
    }
}

class C extends B
{
    public function echoClass()
    {
        echo __CLASS__;
    }

    public function echoA()
    {
        echo 'C';
    }
}

//(new A())->echoClass();
//(new B())->echoClass();
//(new C())->echoClass();
(new C())->test();

