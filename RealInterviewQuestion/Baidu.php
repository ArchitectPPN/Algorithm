<?php


class Baidu
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



