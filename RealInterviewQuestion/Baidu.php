<?php


/*class Baidu
{
    private $arr = [];

    public function __construct(string $str)
    {
        $this->arr = json_decode($str, true);
    }

    public function run()
    {
        return $this->actionCheckScore($this->arr);
    }

    public function checkPass(array $arr)
    {
        $passFlag = true;
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $passFlag =  $this->checkPass($value);
            } else {
                if ($key == 'score' && (!is_int($value) || $value < 60)) {
                    $passFlag = false;
                    break;
                }
            }
        }

        return $passFlag;
    }

    public function actionCheckScore(array $array): bool
    {

        $flag = true;
        foreach ( $array as $key => $val) {

            if(is_array($val)) {
                $this->actionCheckScore($val);
            }else {

                if( $key == 'score' && (!is_int($val) || $val < 60) ) {
                    $flag = false;
                    break;
                }

            }
        }

        return $flag;

    }

}

var_dump((new Baidu(json_encode(['name' => 69, 'score' => 70, 'address' => []])))->run());
*/
class Solution {

    /**
     * @param Integer[] $nums
     * @return NULL
     */
    function moveZeroes(array $nums): ?array
    {

       /* $length = count($nums)-1;

        for($i = 0; $i < $length; $i++) {
            for ( $j = 0; $j < $length - $i; $j++) {
                if ($nums[$j] > $nums[$j+1]) {
                       $min = $nums[$j+1];
                       $max = $nums[$j];

                       $nums[$j] = $min;
                       $nums[$j+1] = $max;
                }
            }
        }*/

        if(count($nums) <= 1) {
            return $nums;
        }

        $middle = $nums[0];
        $left = $right = array();
        $length = count($nums);

        for ($i=1;$i<$length;$i++) {
            if($nums[$i] < $middle) {
                $left[] = $nums[$i];
            }else{
                $right[] = $nums[$i];;
            }
        }

        $left = $this->moveZeroes($left);
        $right = $this->moveZeroes($right);

        return array_merge($left,array($middle),$right);
    }

    /**
     * 获取最长长度的字符和长度
     *
     * @param string $objectStr
     */
    public function getMaxLengthChar(string $objectStr)
    {
        // 定义两个指针,countLength/nowIndex
        $countLength = $nowIndex = 0;
        // 最长的字符
        $maxLengthStr = '';
        // 最长字符长度
        $maxCharLength = 0;
        // 获取当前字符串的长度
        $strLength = strlen($objectStr);

        while ($countLength < $strLength - 1) {
            // 判断当前字符和最长字符指针指向的字符是否一致
            if ($objectStr[$countLength] !== $objectStr[$nowIndex]) {
                // 判断两个距离之间是否大于大于长度
                if (($countLength - $nowIndex) > $maxCharLength) {
                    // 将最大长度替换
                    $maxCharLength = $countLength - $nowIndex;
                    // 最长字符
                    $maxLengthStr = $objectStr[$nowIndex];
                }
                // 使当前指向的字符跟上当前循环到的数值
                $nowIndex = $countLength;
            }

            $countLength++;
        }

        var_dump($maxCharLength, $maxLengthStr);
    }

    public function getMaxSameChar(string $objChar)
    {
        $nowIndex = 0;

        $countLength = strlen($objChar);
        if ($countLength <= 1) {
            var_dump("最大长度:{$countLength}, 最大字符串{$objChar}");
            die;
        }

        $maxRepeatChar = '';
        $maxRepeatCount = '';
        #jjjkkkkkk
        for ($i = 0; $i <= $countLength - 1; $i++) {
            // 当前指向的值不等于最长字符串指针指向的值,就更换最长出现的字符
            if ($objChar[$i] != $objChar[$nowIndex]) {
                // 两者之间的距离大于当前最大距离
                if (($i - $nowIndex) > $maxRepeatCount) {
                    // 更换最大值
                    $maxRepeatCount = $i - $nowIndex;
                    // 更换最大字符
                    $maxRepeatChar = $objChar[$nowIndex];
                }
                $nowIndex = $i;
            } else if($i == $countLength - 1) {
                // 如果到达了末尾
                if (($i - $nowIndex) >= $maxRepeatCount) {
                    // 更换最大值
                    $maxRepeatCount = $i - $nowIndex + 1;
                    // 更换最大字符
                    $maxRepeatChar = $objChar[$nowIndex];
                }
            }
        }

        var_dump("最大长度:{$maxRepeatCount}, 最大字符串{$maxRepeatChar}");
    }

    /**
     * 删除有序字符串中重复的值
     */
    public function delStrSameObj(string $str): string
    {
        $nowIndex = 0;

        for ($i = 0; $i < strlen($str); $i++) {
            // 只有在两个指针相差大于1时执行
            if (($i - $nowIndex) >= 1) {
                // 将后面
                if ($str[$i] != $str[$nowIndex]) {
                    var_dump($i, $nowIndex);
                    $str[$nowIndex + 1] = $str[$i];
                    $nowIndex++;
                }
            }
        }

        var_dump($str);
        return $str;
    }
}

$nums = [0,2,0,7,3,41,26,6,0];
$a = new Solution();
//$a->getMaxLengthChar('lllljkkkkkkk');
$a->getMaxSameChar('pool');
//$a->delStrSameObj('jkl');

//$b = $a->moveZeroes($nums);
//var_dump($b);
//
//
//function testCode($a)
//{
//    $a = str_split($a);
//    $result = '';
//    $k = 0;
//    for ($i = 0, $l = sizeof($a); $i < $l; $i++) {
//        if (isset($a[$i + 1])) {
//            if ($a[$i] == $a[$i + 1]) {
//                $k++;
//            } else {
//                $k++;
//                $result .= $k . $a[$i];
//                $k = 0;
//            }
//        } else {
//            $k++;
//            $result .= $k . $a[$i];
//            $k = 0;
//        }
//    }
//    return $result;
//}
//
//var_dump(testCode('11121'));


