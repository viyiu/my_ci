<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/2/14
 * Time: 14:05
 */
class CI_Benchmark
{
    public $marker = array();

    /**
     * 设置一个基准测试点基准
     * @param string $name 名字
     */
    public function mark($name)
    {
        $this->mark($name);
    }

    /**
     *计算时间
     * @param string $point1 测试点一
     * @param string $point2 测试点2
     * @param int $decimal 小数点分割
     * @return mixed
     */
    public function elapsed_time($point1, $point2, $decimal = 4)
    {
        if ($point1 == '') {
            return '{elapsed_time}';
        }

        if (! isset($this->marker[$point1])) {
            return '';
        }

        if (!isset($this->marker[$point2])) {
            $this->marker[$point2] = microtime(true);
        }

        return number_format($this->marker[$point2] - $this->marker[$point1], $decimal);
    }

    /**
     * 返回内存变量
     */
    public function memory_usage()
    {
        return '{memory_usage}';
    }
}