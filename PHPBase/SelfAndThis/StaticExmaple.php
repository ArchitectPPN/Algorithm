<?php

# 非静态环境下使用static
class A
{
    private function foo()
    {
        echo 'Success' . PHP_EOL;
    }

    public function test1()
    {
        $this->foo();
    }
}

class B extends A
{

}

class C extends B
{
    private function foo()
    {
        echo 111;
    }

    public function test1()
    {
        $this->foo();
    }
}

//(new B())->test();
(new C())->test();


class foo
{
    public function printItem($string)
    {
        echo 'Foo: ' . $string . PHP_EOL;
    }

    public function printPHP()
    {
        echo 'PHP is great.' . PHP_EOL;
    }
}

class cat extends foo
{
    public function printItem($string)
    {
        echo 'Cat: ' . $string . PHP_EOL;
    }
}

class bar extends cat
{
    public function printItem($string)
    {
        echo 'Bar: ' . $string . PHP_EOL;
    }
}