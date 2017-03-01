<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/2/20
 * Time: 10:16
 */
defined("BASEPATH") or exit('no directory to access allowed');

if (is_php('5.5')) {
    return;
}

/**
 * 返回某一列的值
 * @param array $input 需要取出数组列的多维数组
 * @param mixed $column_key 需要返回数组的列
 * @param mixed $index_key 返回数组的索引的列
 * @return mixed 多维数组返回单列数组
 */
if (!function_exists('array_column')) {
    function array_column(array $array, $column_key, $index_key = null) {
        if (!in_array(($type = gettype($column_key)), array('integer', 'string', 'null'), true)) {
            if ($type === 'double') {
                $column_key = (int)$column_key;
            } elseif ($type === 'object' && method_exists('object', '__toString')) {
                $column_key = (string)$column_key;
            } else {
                trigger_error('array_column():expect the column_key is int or string:'. $column_key, E_USER_WARNING);
                return false;
            }
        }

        if (!in_array(($type = gettype($index_key)), array('integer', 'string', 'null'), true)) {
            if ($type === 'double') {
                $index_key = (int)$index_key;
            } elseif ($type === 'object' && method_exists($index_key, '__toString')) {
                $index_key = (string)$index_key;
            } else {
                trigger_error('array_column():expect the index_key either string or integer', E_USER_WARNING);
                return false;
            }
        }

        $result = array();
        foreach ($array as &$a) {
            if ($column_key === null) {
                $value = $a;
            } elseif (is_array($a) && key_exists($column_key, $a)) {
                $value = $a[$column_key];
            } else {
                continue;
            }

            if ($index_key === null or key_exists($index_key, $a)) {
                $result[] = $value;
            } else {
                $result[$a[$index_key]] = $value;
            }
        }
        return $result;
    }
}

if (is_php('5.4')) {
    return;
}

/**
 * 转换十六进制字符串为二进制字符串
 * @param array $data 十六进制的数据
 */
if (!function_exists('hex2bin')) {
    function hex2bin($data) {
        if (in_array(($type = gettype($data)), array('array', 'resource', 'object', 'double'), true)) {
            if ($type === 'object' && method_exists($data, '__toString')) {
                $data = (string)$data;
            } else {
                trigger_error('hex2bin() expect a string', E_USER_WARNING);
                return false;
            }
        }

        if (strlen($data) % 2 != 0) {
            trigger_error('hexadecimal input string must be have an even length', E_USER_WARNING);
            return false;
        } elseif (!preg_match("/^[0-9a-f]*$/", $data)) {
            trigger_error('input string must be hexadecimal', E_USER_WARNING);
            return false;
        }

        return pack("H*", $data);
    }
}
