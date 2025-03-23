<?php

class PopSort
{
    /** @var array 需要排序的数组 */
    private array $outOfOrder = [];
    /** @var int 需要排序数组的长度 */
    private int $outOfOrderLength = 0;

    public function __construct(array $outOfOrder)
    {
        $this->outOfOrder = $outOfOrder;
        $this->outOfOrderLength = count($this->outOfOrder);
    }

    /**
     * 冒泡排序
     * 1. 比较相邻两个数字的大小, 如果第一个比第二个大, 交换位置
     * 2. 对每一组相邻数据做同样的工作,从开始到结尾的最后一对. 到此最后一位将是最大或最小的值
     * 3. 针对所有重复的元素重复上面的步骤, 除了最大或最小的那个值
     * 4. 持续每次对越来越少的元素重复上面的步骤，直到没有任何一对数字需要比较
     *
     * @return array
     */
    public function popSort(): array
    {
        for ($startPos = 0; $startPos < $this->outOfOrderLength; $startPos++) {
            for ($second = $startPos + 1; $second < $this->outOfOrderLength; $second++) {
                if ($this->outOfOrder[$startPos] < $this->outOfOrder[$second]) {
                    $this->swapPos($startPos, $second);
                }
            }
        }

        return $this->outOfOrder;
    }

    /**
     * 交换位置
     *
     * @param int $minPos
     * @param int $swapPos
     */
    private function swapPos(int $minPos, int $swapPos): void
    {
        $temp = $this->outOfOrder[$swapPos];
        $this->outOfOrder[$swapPos] = $this->outOfOrder[$minPos];
        $this->outOfOrder[$minPos] = $temp;
    }
}

$outOfOrder = [1, 15, 2, 6, 5, 4, 8, 7, 9, 10, 3, 14, 12, 11, 13];

// 冒泡排序
$sortOrder = new PopSort($outOfOrder)->popSort();
print_r($sortOrder);