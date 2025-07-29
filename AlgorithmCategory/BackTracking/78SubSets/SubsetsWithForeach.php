<?php

namespace SubsetsSolution;

class SubsetsWithForeach
{
    /**
     * @param Integer[] $nums
     * @return Integer[][]
     */
    function subsets(array $nums): array
    {
        $ans = [[]];
        if (empty($nums)) {
            return $ans;
        }

        foreach ($nums as $num) {
            $copyAns = $ans;
            foreach ($copyAns as $key => $val) {
                $val[] = $num;
                $copyAns[$key] = $val;
            }

            $ans = array_merge($ans, $copyAns);
        }

        return $ans;
    }
}

$svc = new SubsetsWithForeach();
$ans = $svc->subsets([1, 2, 3]);

var_dump($ans);