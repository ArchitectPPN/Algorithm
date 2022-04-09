<?php


namespace Observer;


class Message
{
    public $message = '';

    public function __construct($message)
    {
        $this->message = $message;
    }
}