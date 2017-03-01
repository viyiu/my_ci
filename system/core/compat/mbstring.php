<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/2/16
 * Time: 10:29
 */
defined('BASEPATH') or exit('no directory to access');

if (MB_ENABLE == true) {
    return;
}


/**
 * @param string $str 字符串
 * @param string $encoding 字符集
 * @return string
 */
if (!function_exists('mb_strlen')) {
    function mb_strlen($str, $encoding = null)
    {
        if (ICONV_ENABLE == true) {
            return iconv_strlen($str, isset($encoding) ? $encoding : config_item('charset'));
        }
        return strlen($str);
    }
}

/**
 * 多字节的位置
 * @param string $haystack 搜索的东西
 * @param string $needle
 * @param int $offset
 * @param string $charset 字符集
 * @return string
 */
if (!function_exists('mb_strpos')) {
    function mb_strpos($haystack, $needle, $offset = 0, $charset = null) {
        if (ICONV_ENABLE == true) {
            return iconv_strpos($haystack, $needle, $offset, isset($charset) ? $charset : config_item('charset'));
        }
        return strpos($haystack, $needle, $offset, isset($charset) ? $charset : config_item('charset'));
    }
}

/**
 * 字符串截取
 * @param string $str 字符串
 * @param int $start 开始
 * @param int $length 长度
 * @param string $encoding 字符集
 * @return string
 */
if (!function_exists('mb_substr')) {
    function mb_substr($str, $start, $length = null, $encoding = null)
    {
        if (ICONV_ENABLE == true) {
            isset($encoding) or config_item('encoding');
            return iconv_substr(
                $str,
                $start,
                isset($length) ? $length : iconv_strlen($str, $length),
                $encoding
            );
        }

        return isset($length) ? substr($str, $start, $length) : substr($str, $start);
    }
}