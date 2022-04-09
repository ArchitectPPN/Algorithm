<?php


namespace Observer;


class ConcreteObserverSendMsg implements Observer
{
    public function update(Message $message)
    {
        echo "发送短信：" . $message->message . PHP_EOL;
    }
}