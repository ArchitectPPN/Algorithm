<?php


namespace Observer;


interface Subject
{
    // 注册观察者
    public function registerObserver(Observer $observer);

    // 移除观察者
    public function removeObserver(Observer $observer);

    public function notifyObservers(Message $message);
}