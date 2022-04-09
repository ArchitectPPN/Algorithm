<?php


namespace Observer;


interface RegSubject
{
    public function registerObserver(RegObserve $observer);

    public function removeObserver(RegObserve $observer);

    public function notifyObservers($userId);
}