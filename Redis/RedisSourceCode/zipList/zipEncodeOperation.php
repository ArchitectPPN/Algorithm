<?php

const ZIP_STR_14B = 1 << 6;
const ZIP_STR_32B = 2 << 6;

$rawLen = 16320;
$operate = '0x3f';

$zipStr32b = decbin(ZIP_STR_32B);
echo "ZIP_STR_32B的二进制为: " . $zipStr32b . " 长度为:" . strlen($zipStr32b) . PHP_EOL;

$zipStr14b = decbin(ZIP_STR_14B);
echo "ZIP_STR_14B的二进制为: 0" . $zipStr14b . " 长度为:" . strlen($zipStr14b) . PHP_EOL;
echo "0x3f的二进制表示为:" . decbin(0x3f) . PHP_EOL;

// 转化为二进制
$rawLenBinary = decbin($rawLen);
echo "rawLen的二进制为 : " . $rawLenBinary . "长度为: " . strlen($rawLenBinary) . PHP_EOL;

// rawlen 右移8位
$rawLenBinaryMoveRight = decbin($rawLen >> 8);
echo "rawLen右移八位后 : " . $rawLenBinaryMoveRight . "长度为: " . strlen($rawLenBinaryMoveRight) . PHP_EOL;

$decimalValue = hexdec($operate);
$operateBinary = decbin($decimalValue);
$directBinary = decbin(0x3f);
echo "operate直接转为二进制: " . $directBinary . " 长度为: " . strlen($directBinary) . PHP_EOL;
echo "operate转为十进制 : " . $decimalValue . "转为二进制: " . $operateBinary . " operateBinary长度为: " . strlen(
        $operateBinary
    ) . PHP_EOL;

$zeroXff = decbin(0xff);
echo "zeroXff的二进制为 : " . $zeroXff . "长度为: " . strlen($zeroXff) . PHP_EOL;

// 进行与操作
$result = $rawLenBinaryMoveRight & $operate;
$ans = ZIP_STR_14B | (($rawLen >> 8) & 0x3f);
echo 'ZIP_STR_14B | (($rawLen >> 8) & 0x3f): ' . $ans . PHP_EOL . PHP_EOL;


echo "rawLen的二进制为 : " . $rawLenBinary . "长度为: " . strlen($rawLenBinary) . PHP_EOL;
echo "zeroXff的二进制为 :      " . $zeroXff . "长度为: " . strlen($zeroXff) . PHP_EOL;
# 11111100
//echo "bindec(11111100): " . bindec("11111100") . PHP_EOL;
$buf2 = $rawLen & 0xff;
echo "rawlen & 0xff:" . $buf2 . PHP_EOL . PHP_EOL;

echo "zipStr14b:              0" . $zipStr14b . PHP_EOL;
echo "0xff:                   " . $zeroXff . PHP_EOL;
echo "rawLenBinaryMoveRight:  00" . $rawLenBinaryMoveRight . PHP_EOL;
echo "0x3f:                   00" . $directBinary . PHP_EOL;
echo "rawLenBinary:   00" . $rawLenBinary . PHP_EOL . PHP_EOL;

echo "---------------encode-------------------" . PHP_EOL;
echo 'ZIP_STR_14B | (($rawLen >> 8) & 0x3f): ' . $ans . " binary:" . decbin($ans) . PHP_EOL;
echo "rawlen & 0xff:                       " . $buf2 . " binary:" . decbin($buf2) . PHP_EOL;
echo "---------------encode-------------------" . PHP_EOL . PHP_EOL;

echo "拼接后的二进制: " . decbin($ans) . decbin($buf2) . PHP_EOL;
echo "直接拼接是错误的, 多一个 ZIP_STR_14B 标记位, 要将其移除掉: " . bindec(
        decbin($ans) . decbin($buf2)
    ) . PHP_EOL . PHP_EOL;

echo "---------------decode-------------------" . PHP_EOL;
// 检查是否设置了 ZIP_STR_14B
if ($ans & ZIP_STR_14B) {
    // 只保留低六位
    $lowSixBite = $ans & 0x3f;
    // 向左移动8位
    $highSixBite = $lowSixBite << 8;

    // 解码低8位, 只保留低八位
    $rawLenBinary = $highSixBite | $buf2 & 0xff;

    echo "decode: " . $rawLenBinary . " Binary: " . decbin($rawLenBinary) . PHP_EOL;
}

echo "---------------decode-------------------" . PHP_EOL;

echo "---------------over16BitEncode-------------------" . PHP_EOL;
$over16Size = 4194967296;
$over16SizeBinary = decbin($over16Size);
echo "over16Size: " . $over16Size . " over16SizeBinary: " . $over16SizeBinary . " over16SizeBinaryLength: " . strlen(
        $over16SizeBinary
    ) . PHP_EOL;

$moveRight24 = $over16Size >> 24;
$moveRight24Binary = decbin($moveRight24 & 0xff);
$binarySizeBuf[] = $moveRight24Binary;
echo "moveRight24: " . $moveRight24 . " moveRight24Binary: " . $moveRight24Binary . " moveRight24BinaryLength: " . strlen(
        $moveRight24Binary
    ) . PHP_EOL;

$moveRight16 = $over16Size >> 16;
$moveRight16Binary = decbin($moveRight16 & 0xff);
$binarySizeBuf[] = $moveRight16Binary;
echo "moveRight16: " . $moveRight16 . " moveRight16Binary: " . $moveRight16Binary . " moveRight16BinaryLength: " . strlen(
        $moveRight16Binary
    ) . PHP_EOL;

$moveRight8 = $over16Size >> 8;
$moveRight8Binary = decbin($moveRight8 & 0xff);
$binarySizeBuf[] = $moveRight8Binary;
echo "moveRight8: " . $moveRight8 . " moveRight8Binary: " . $moveRight8Binary . " moveRight8BinaryLength: " . strlen(
        $moveRight8Binary
    ) . PHP_EOL;

$moveRight8Binary = decbin($over16Size & 0xff);

$binarySizeBuf[] = $moveRight8Binary;

var_dump($binarySizeBuf);

$binarySizeBuf2[] = decbin(($over16Size >> 24) & 0xff);
$binarySizeBuf2[] = decbin(($over16Size >> 16) & 0xff);
$binarySizeBuf2[] = decbin(($over16Size >> 8) & 0xff);
$binarySizeBuf2[] = decbin($over16Size & 0xff);

var_dump($binarySizeBuf2);

# 1111 1010 0000 1010 0001 1111 0000 0000 >> 24 & 0xff = 1111 1010
# 1111 1010 0000 1010 0001 1111 0000 0000 >> 16 & 0xff = 0000 1010
# 1111 1010 0000 1010 0001 1111 0000 0000 >> 8 & 0xff = 0001 1111
# 1111 1010 0000 1010 0001 1111 0000 0000 & 0xff = 0000 0000

echo "---------------over16BitEncode-------------------" . PHP_EOL;

echo "---------------over16BitDecode-------------------" . PHP_EOL;

$decode = str_pad($binarySizeBuf2[0], 8, '0', STR_PAD_LEFT) . str_pad($binarySizeBuf2[1], 8, '0', STR_PAD_LEFT) . str_pad($binarySizeBuf2[2], 8, '0', STR_PAD_LEFT) . str_pad($binarySizeBuf2[3], 8, '0', STR_PAD_LEFT);
echo "decode: " . bindec($decode) . PHP_EOL;

echo "---------------over16BitDecode-------------------" . PHP_EOL;