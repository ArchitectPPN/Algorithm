<?php

namespace DecodeStringSolution;

class SolutionWithStack
{
    /**
     * @param String $s
     * @return String
     */
    function decodeString(string $s): string
    {
        $stack = [];
        $num = 0;
        $str = '';

        for ($i = 0; $i < strlen($s); $i++) {
            $c = $s[$i];

            if (is_numeric($c)) {
                $num = $num * 10 + intval($c);
            } elseif ($c === '[') {
                $stack[] = [
                    $str,
                    $num,
                ];
                $str = '';
                $num = 0;
            } elseif ($c === ']') {
                [
                    $prevStr,
                    $multiplier,
                ] = array_pop($stack);
                $str = $prevStr . str_repeat($str, $multiplier);
            } else { // 字母
                $str .= $c;
            }
        }

        return $str;
    }
}

$solution = new SolutionWithStack();
$answer = $solution->decodeString("100[a]");

echo $answer . PHP_EOL;

class SolutionWithRecursion
{
    /**
     * @param String $s
     * @return String
     */
    public function decodeString(string $s): string
    {
        return $this->solver($s, 0)[0];
    }

    /**
     * @param string $s
     * @param int $start
     * @return array
     */
    private function solver(string $s, int $start): array
    {
        $repeatNum = 0;
        $str = '';

        while ($start < strlen($s)) {
            if (is_numeric($s[$start])) {
                $repeatNum = $repeatNum * 10 + intval($s[$start]);
            } else if ($s[$start] === '[') {
                $subAns = $this->solver($s, $start + 1);
                while ($repeatNum > 0) {
                    $str .= $subAns[0];
                    $repeatNum--;
                }
                // 更新start
                $start = $subAns[1];
                $repeatNum = 0;
            } else if ($s[$start] === ']') {
                // 说明已找到子问题得答案， 返回
                return [$str, $start];
            } else {
                $str .= $s[$start];
            }
            $start++;
        }

        return [$str, $start];
    }
}

$solution = new SolutionWithStack();
$answer = $solution->decodeString("100[a]");

echo $answer . PHP_EOL;

