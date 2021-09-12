<?php


class SimpleLRU146
{
    private $capacity = 0;
    // 保存最近使用到的元素
//    private $list = ['first' => 'first', 'second' => 'second', 'three' => 'three'];
    private $list = [];

    public function __construct($capacity)
    {
        // 设置长度
        $this->capacity = $capacity;
    }

    /**
     * Get the key's value
     *
     * @param $key
     * @return int|mixed
     */
    public function get($key)
    {
        // 检查如果存在这个值, 因为使用了,将其移动到数组头部
        if (isset($this->list[$key])) {
            // 得到当前key的值
            $value = $this->list[$key];
            // 将其移除
            unset($this->list[$key]);

            $this->list = array_merge([$key => $value], $this->list);

            return $value;
        }

        return -1;
    }

    public function __get($value) {
        return $this->$value;
    }

    /**
     * set key's value
     *
     * @param $key
     * @param $value
     */
    public function put($key, $value)
    {
        if ($this->capacity === count($this->list)) {
            // 淘汰策略: 移除最后一个元素
            array_pop($this->list);
        } else {
            // 检查是否存在该元素
            if (isset($this->list[$key])) {
                // 移除掉当前元素
                unset($this->list[$key]);
            }
        }

        $this->list = array_merge([$key => $value], $this->list);
    }
}

$lru = new SimpleLRU146(10);

var_dump($lru->get('key'));
$lru->put('name', 'value');
$lru->put('sex', 'man');
$lru->put('age', 12);

$lru->get('name');

var_dump($lru->list);
