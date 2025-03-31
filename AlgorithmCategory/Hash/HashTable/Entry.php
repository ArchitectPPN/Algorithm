<?php

class Entry
{
    /** @var string */
    private string $key;

    /** @var string */
    private string $value;

    /** @var entry|null */
    private ?entry $next;

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getNext(): ?entry
    {
        return $this->next;
    }

    public function setNext(?entry $next): void
    {
        $this->next = $next;
    }
}