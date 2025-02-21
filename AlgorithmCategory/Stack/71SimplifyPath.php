<?php
# 2025年2月21日

# 71. 简化路径 https://leetcode.cn/problems/simplify-path/description/

class SimplifyPathSolution
{
    /**
     * Thinking:
     * 按照 "/" 将字符串路径进行分割, 然后对分割出来的路径进行判断
     * 如果是 "." 直接丢掉;
     * 然后将分割出来的路径遍历一遍, 放入到一个栈中, 遇到".."时, 栈顶元素出栈;
     * 最后将栈中元素使用 "/" 连接起来, 并且加上开头的 "/"
     * @param string $path
     * @return string
     */
    public function simplifyPath(string $path): string
    {
        $sLen = strlen($path);
        $splitPath = [];
        $tmpPath = "";
        for ($i = 0; $i < $sLen; $i++) {
            // 当前元素是 "/", 检查是否保留当前的路径
            if ($path[$i] == "/") {
                if ($tmpPath != "." && $tmpPath != "") {
                    $splitPath[] = $tmpPath;
                }
                $tmpPath = "";
                continue;
            }
            $tmpPath .= $path[$i];
        }
        // 将最后一个元素放入split中
        if ($tmpPath != "." && $tmpPath != "") {
            $splitPath[] = $tmpPath;
        }

        $stack = [];
        foreach ($splitPath as $tmpPath) {
            // 栈不为空, 遇到".."时, 栈顶元素出栈;
            if ($tmpPath == "..") {
                $stack && array_pop($stack);
                continue;
            }

            $stack[] = $tmpPath;
        }

        return '/' . implode('/', $stack);
    }
}

$question = "/../d/s/p/../k";

$svc = new SimplifyPathSolution();
echo $svc->simplifyPath($question);