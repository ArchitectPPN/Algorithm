<?php

class generateOutOfOrderArray
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

class Sort
{
    private $outOfOrder = [];
    private $outOfOrderLength = 0;

    public function __construct(array $outOfOrder)
    {
        $this->outOfOrder = $outOfOrder;
        $this->outOfOrderLength = count($this->outOfOrder);
    }

    /**
     * 选择排序
     *
     * @return array
     */
    public function selectSort(): array
    {
        for ($firstStartPos = 0; $firstStartPos < $this->outOfOrderLength; $firstStartPos++) {
            // 初始化最小位置, 将第一次循环的位置视为最小最值, 每次都从最小位置开始循环
            $minPos = $firstStartPos;
            // 循环找到数据中最小的数据
            for ($secondStartPos = $firstStartPos + 1; $secondStartPos < $this->outOfOrderLength; $secondStartPos++) {
                $minPos = $this->outOfOrder[$secondStartPos] < $this->outOfOrder[$minPos] ? $secondStartPos : $minPos;
            }

            // 交换位置
            $this->swapPos($minPos, $firstStartPos);
        }

        return $this->outOfOrder;
    }

    public function popSort(): array
    {

    }

    /**
     * 交换位置
     *
     * @param int $minPos
     * @param int $swapPos
     */
    private function swapPos(int $minPos, int $swapPos)
    {
        $temp = $this->outOfOrder[$swapPos];
        $this->outOfOrder[$swapPos] = $this->outOfOrder[$minPos];
        $this->outOfOrder[$minPos] = $temp;
    }
}

$outOfOrder = (new generateOutOfOrderArray(5))->get();

$sortOrder = (new Sort($outOfOrder))->selectSort();
print_r($sortOrder);

