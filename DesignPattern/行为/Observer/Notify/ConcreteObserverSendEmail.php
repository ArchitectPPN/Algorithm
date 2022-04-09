<?php


namespace Observer;


class ConcreteObserverSendEmail implements Observer
{
    public function update(Message $message)
    {
        echo "发送邮件：" . $message->message . PHP_EOL;
    }
}