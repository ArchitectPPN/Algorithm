<?php


namespace Observer\Imp;

use Observer\RegSubject;
use Observer\RegObserve;

class RegConcreteObserver implements RegSubject
{
    public $observer = [];

    public function registerObserver(RegObserve $regObserve)
    {
        $this->observer[get_class($regObserve)] = $regObserve;
    }

    public function removeObserver(RegObserve $regObserve)
    {
        unset($this->observer[get_class($regObserve)]);
    }

    public function notifyObservers($userId)
    {
        foreach ($this->observer as $observe) {
            $observe->handleRegSuccess($userId);
        }
    }
}