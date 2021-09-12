<?php


class BaseClass
{
    public function __construct()
    {
        echo "In Base Controller!" . PHP_EOL;
    }
}

class SubClass extends BaseClass
{
    public function __construct()
    {
        echo "In Sub Controller!" . PHP_EOL;
        parent::__construct();

    }
}

(new SubClass());
