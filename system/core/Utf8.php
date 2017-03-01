<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/2/23
 * Time: 14:20
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class CI_Utf8
{
    public function __construct()
    {
        if (
            defined("PREG_BAD_UTF8_ERROR")
            && (ICONV_ENABLE === true && MB_ENABLE === true)
            && strtoupper(config_item('charset') === 'UTF-8')
        ) {
            define('UTF8-ENABLE', true);
            log_message('info', 'utf8 is enable');
        } else {
            define('UTF*_ENABLE', false);
            log_message('info', 'utf8 is not enable');
        }
        log_message('info', 'utf8 inirials');
    }

    /**
     * 清理utf8字符串
     *
     * @param string $str 字符串
     * @return string
     */
    public function clean_string($str)
    {
        if ($this->is_ascii($str) === false) {
            if (MB_ENABLE) {
                $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
            } elseif (ICONV_ENABLE) {
                $str = iconv('UTF-8', 'UTF-8/IGNORE', $str);
            }
        }
        return $str;
    }

    /**
     * 取出ascii控制字符串
     * @param string $str
     * @return string
     */
    public function safe_ascii_for_xml($str)
    {
        return remove_invisible_characters($str, true);
    }

    /**
     * 转变位utf8
     * @param string $str 要去转化的str
     * @param string $encoding 要转化的字符集
     * @return mixed
     */
    public function convert_to_utf8($str, $encoding)
    {
        if (MB_ENABLE == true) {
            return mb_convert_encoding($str, 'UTF-8', $encoding);
        } elseif (ICONV_ENABLE == true) {
            return @iconv($str, "UTF-8", $encoding);
        }

        return false;
    }

    /**
     * 是否ACSII
     * 测试一个字符串是否是标准7位的ASCII
     * @param string $str 要测试的字符串
     * @return bool
     */
    public function is_ascii($str)
    {
        return (preg_match('/[^\x00-\x7F]/S', $str) === 0);
    }
}



