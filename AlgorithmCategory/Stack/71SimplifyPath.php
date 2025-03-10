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

//$question = "/../d/s/p/../k";

//$svc = new SimplifyPathSolution();
//echo $svc->simplifyPath($question);

class SimplifyPathSolutionReviewOne
{
    /**
     * Thinking:
     * 将目录按照 "/" 进行分割, 然后对分割出来的路径进行判断
     * 如果是 "." 直接丢掉;
     * 如果是 ".." 时, 栈不为空时, 栈顶元素出栈;
     * @param string $path
     * @return string
     */
    public function simplifyPath(string $path): string
    {
        // 初始化开始的元素
        $startIndex = 0;
        $sLen = strlen($path);
        $filePathStack = [];

        while ($startIndex < $sLen) {
            // 过滤掉 "/"
            while ($path[$startIndex] == "/" && $startIndex < $sLen) {
                $startIndex++;
            }

            // 拼接两个"/"之间的路径
            $tmpPath = "";
            while ($startIndex < $sLen && $path[$startIndex] != "/") {
                $tmpPath .= $path[$startIndex];
                $startIndex++;
            }

            if ($tmpPath == "..") {
                $filePathStack && array_pop($filePathStack);
            } elseif ($tmpPath != "." && $tmpPath != "") {
                $filePathStack[] = $tmpPath;
            }
        }

        return '/' . implode('/', $filePathStack);
    }
}

$question = "/home/";
$svc = new SimplifyPathSolutionReviewOne();
echo $svc->simplifyPath($question);

# 2025年2月24日
class SimplifyPathSolutionReviewTwo
{
    /**
     * Thinking:
     * 将目录按照 "/" 进行分割, 然后对分割出来的路径进行判断
     * 如果是 "." 直接丢掉;
     * 如果是 ".." 时, 栈不为空时, 栈顶元素出栈;
     * @param string $path
     * @return string
     */
    public function simplifyPath(string $path): string
    {
        // 初始化循环开始的下标
        $startIndex = 0;
        // 字符串的长度
        $sLen = strlen($path);
        // 路径栈
        $pathStack = [];

        while ($startIndex < $sLen) {
            // 过滤掉所有的 "/"
            while ($path[$startIndex] == "/" && $startIndex < $sLen) {
                $startIndex++;
            }

            $tmpPath = "";
            // 拼接两个"/"之间的路径
            while ($path[$startIndex] != "/" && $startIndex < $sLen) {
                $tmpPath .= $path[$startIndex];
                $startIndex++;
            }
            // 判断当前路径是否是 "..", 栈不为空时, 栈顶元素出栈
            if ($tmpPath == '..') {
                $pathStack && array_pop($pathStack);
            } elseif ($tmpPath != '.' && $tmpPath != '') {
                $pathStack[] = $tmpPath;
            }
        }

        return '/' . implode('/', $pathStack);
    }
}

$question = "/home/";
$svc = new SimplifyPathSolutionReviewTwo();
echo $svc->simplifyPath($question);

# 2025年3月3日

/**
 * thinking:
 * 整体思路就是将路径按照 '/' 进行分割, 然后对每一部份进行处理
 * 如果当前部分为 . 或者 ""(空字符) 就舍弃
 * 如果当前部分为 .. 就弹出栈顶元素
 */
class SimplifyPathSolutionReviewThree
{
    /**
     * @param string $path
     * @return string
     */
    function simplifyPath(string $path): string
    {
        $startIndex = 0;
        $sLen = strlen($path);
        $pathStack = [];

        while ($startIndex < $sLen) {
            // 过滤掉所有的 /
            while ($path[$startIndex] == "/" && $startIndex < $sLen) {
                $startIndex++;
            }

            $tmpPath = "";
            while ($startIndex < $sLen && $path[$startIndex] != "/") {
                // 拼接上当前元素
                $tmpPath .= $path[$startIndex];
            }

            if ($tmpPath == "..") {
                $pathStack && array_pop($pathStack);
            } elseif ($tmpPath != "." && $tmpPath != "") {
                $pathStack[] = $tmpPath;
            }
        }

        return '/' . implode('/', $pathStack);
    }
}

$question = "/home";
$svc = new SimplifyPathSolutionReviewThree();
echo $svc->simplifyPath($question) . PHP_EOL;

# 2025年3月10日

class SimplifyPathSolutionReviewFour
{
    /**
     * @param string $path
     * @return string
     */
    public function simplifyPath(string $path): string
    {
        $sLen = strlen($path);
        $startIndex = 0;
        $pathStack = [];

        while ($startIndex < $sLen) {
            // 过滤掉 /
            while ($path[$startIndex] == "/" && $startIndex < $sLen) {
                $startIndex++;
            }

            $currentPath = "";
            while ($path[$startIndex] != "/" && $startIndex < $sLen) {
                $currentPath .= $path[$startIndex];
                $startIndex++;
            }

            if ($currentPath == "..") {
                $pathStack && array_pop($pathStack);
            } else if ($currentPath != '.' && $currentPath != '') {
                $pathStack[] = $currentPath;
            }
        }

        return '/' . implode('/', $pathStack);
    }
}