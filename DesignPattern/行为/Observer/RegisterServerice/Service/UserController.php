<?php
namespace Service;

use Observer\Imp\RegConcreteObserver;
use Observer\Imp\RegPromotionObserver;
use Observer\Imp\RegSendMsgObserver;

class UserController
{
    public function registerUser()
    {
        $userId = 100 . mt_rand(100000, 999999);

        $concreteObserve = new RegConcreteObserver();

        $concreteObserve->registerObserver(new RegSendMsgObserver());
        $concreteObserve->registerObserver(new RegPromotionObserver());

        $concreteObserve->notifyObservers($userId);
    }
}