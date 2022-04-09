<?php


namespace Observer;


interface Observer
{
    public function update(Message $message);
}