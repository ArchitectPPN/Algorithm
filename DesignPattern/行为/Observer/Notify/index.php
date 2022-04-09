<?php

require_once("Message.php");
require_once("Observer.php");
require_once("Subject.php");
require_once("ConcreteSubject.php");
require_once("ConcreteObserverSendEmail.php");
require_once("ConcreteObserverSendMsg.php");


class Demo
{
    public function index()
    {
        $subject = new \Observer\ConcreteSubject();
        $subject->registerObserver(new \Observer\ConcreteObserverSendMsg());
        $subject->registerObserver(new \Observer\ConcreteObserverSendEmail());
        $subject->notifyObservers(new \Observer\Message('您的订单已经发货了， 订单号： T1000202204092030'));
    }
}

$demo = new Demo();
$demo->index();