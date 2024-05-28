<?php
$file = './dump.rdb'; // 更改为你的文件路径

$fp = fopen($file, 'rb');
if ($fp) {
    while (!feof($fp)) {
        $byte = fread($fp, 1);
        if ($byte !== false) {
            // 使用unpack函数将字节转换为二进制字符串，并使用str_pad确保它总是8位长
            $bin = str_pad(base_convert(bin2hex($byte), 16, 2), 8, '0', STR_PAD_LEFT);
            echo $bin;
        }
    }
    fclose($fp);
}