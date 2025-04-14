<?php

# 860. 柠檬水找零 https://leetcode.cn/problems/lemonade-change/description/

class LemonadeChangeSolution
{
    /**
     * THINKING
     * 首先账单只有5，10，20
     * 5不需要找零
     * 10块需要一张5块
     * 20有两种找零方案：
     *  1. 一张5块，一张10块；
     *  2. 三张5块；
     * 所以我们就可以根据情况来进行分析，根据每个钱币的大小来判断当前拥有的钱币是否可以找零；
     * 答案就比较明显了，
     * 5块不需要找零，直接跳过；
     * 10块需要有一张5块才能找零，如果5元钱币的数量小于1，说明无法找零，直接return false；
     * 20块找零有两种方案，1. 必须有一张5元， 必须有一张10元；2. 直接有三张5元；
     * @param array $bills
     * @return bool
     */
    public function lemonadeChange(array $bills): bool
    {
        $coinsArr = [
            5 => 0,
            10 => 0
        ];

        foreach ($bills as $bill) {
            // 5块 10块的数量都是0， 第一张就大于5块， 说明一定无法找零
            if ($coinsArr[5] == 0 && $coinsArr[10] == 0 && $bill > 5) {
                return false;
            }

            // 5块的钱币不需要找零
            if ($bill == 5) {
                $coinsArr[5]++;
                continue;
            }

            // 10块 20块 需要找零
            if ($bill == 10) {
                if ($coinsArr[5] == 0) {
                    return false;
                }

                // 开始找零， 10块的只能使用5块的找零，所以 5块钱数量-1， 10元 + 1
                $coinsArr[5]--;
                $coinsArr[10]++;
                continue;
            }

            // 20块找零
            if ($bill == 20) {
                // 20块找零有两种方法
                // 1. 一个10块，一张5块
                // 2. 三张5块
                if ($coinsArr[5] >= 1 && $coinsArr[10] >= 1) {
                    $coinsArr[5] -= 2;
                    $coinsArr[10] -= 1;
                    continue;
                } else if ($coinsArr[5] >= 3) {
                    $coinsArr[5] -= 3;
                    continue;
                }
                return false;
            }
        }

        return true;
    }
}

$bills = [5,5,5,5,10,5,10,10,10,20];

$svc = new LemonadeChangeSolution();
var_dump($svc->lemonadeChange($bills));