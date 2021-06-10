<?php


class Solution
{
    private $arr = [];
    private $length = 0;

    public function __construct(array $arr)
    {
        $this->arr = $arr;
        $this->length = count($this->arr);
    }

    public function removeDuplicates()
    {
        // 初始化后面一个index
        $laterIndex = 1;

        for ($beforeIndex = 0; $beforeIndex < $this->length; $beforeIndex++) {
            if ($this->arr[$beforeIndex] == $this->arr[$laterIndex]) {
                unset($this->arr[$beforeIndex]);
            }
            $laterIndex++;
        }

        var_dump($this->arr, count($this->arr));
    }
}

$testArr = [1,2,3,3,3,4,5,6,7,7,7];
$testArr = [0,0,1,1,1,2,2,3,3,4];

(new Solution($testArr))->removeDuplicates();



