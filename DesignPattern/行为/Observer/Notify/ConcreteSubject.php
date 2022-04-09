<?php


namespace Observer;


class ConcreteSubject implements Subject
{
    private $observers = [];

    public function registerObserver(Observer $observer)
    {
        $this->observers[get_class($observer)] = $observer;
    }

    public function removeObserver(Observer $observer)
    {
        unset($this->observers[get_class($observer)]);
    }

    public function notifyObservers(Message $message)
    {
        foreach ($this->observers as $object) {
            $object->update($message);
        }
    }
}