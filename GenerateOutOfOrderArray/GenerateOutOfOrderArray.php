<?php

class GenerateOutOfOrderArray
{
    private $length = 0;

    private $outOfOrder = [];

    public function __construct(int $length = 10)
    {
        $this->length = $length;
    }

    /**
     * 获取一组随机数
     *
     * @return array
     */
    public function get(): array
    {
        for ($start = 0; $start < $this->length; $start++) {
            $this->outOfOrder[] = mt_rand(0, 10 * $this->length);
        }

        return $this->outOfOrder;
    }
}