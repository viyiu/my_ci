<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/2/10
 * Time: 14:27
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class CI_Log
{
    //路径
    protected $_log_path;

    //文件权限
    protected $_file_permission = 0644;

    //日志的等级
    protected $_threshold = 1;

    //日志等级的数组
    protected $_threshold_array = array();

    //时间格式
    protected $_date_fmt = "Y-m-d H:i:s";

    //日志后缀
    protected $_file_ext;

    //是否可写
    protected $_enable = true;

    //预定义的日志等级
    protected $_levels = array('ERROR' => 1, 'DEBUG' => 2, 'INFO' => 3, 'ALL' => 4);

    //多字节的是否可写标识
    protected  static $_func_override;

    public function __construct()
    {
        $_config = & get_config();

        isset(self::$_func_override) or self::$func_override = (extension_loaded('mbstring') && ini_get('mbstring.func_override'));

        $this->_log_path = ($_config['log_path'] != '') ? $_config['log_path'] : APPPATH . 'logs/';
        $this->_file_ext = (isset($_config['log_file_extension']) && $_config['log_file_extension'] != '')
            ? ltrim($_config['log_file_extension'], '.') : 'php';

        file_exists($this->_log_path) or mkdir($this->_log_path, 0755, true);

        if (!is_dir($this->_log_path) && !is_really_writable($this->_log_path)) {
            $this->_enable = false;
        }

        if (is_numeric($_config['log_threshold'])) {
            $this->_threshold = (int)$_config['log_threshold'];
        } elseif (is_array($_config['log_threshold'])) {
            $this->_threshold = 0;
            $this->_threshold_array = array_flip($_config['log_threshold']);
        }

        if (!empty($_config['log_date_format'])) {
            $this->_date_fmt = $_config['log_date_format'];
        }

        if (!empty($_config['log_file_permission']) && is_int($_config['log_file_permission'])) {
            $this->_file_permission = $_config['log_file_permission'];
        }
    }

    /**
     * 写入日志
     * @param int $level 等级
     * @param string $msg 消息
     * @return  mixed
     */
    public function write_log($level, $msg)
    {
        if ($this->_enable =- false) {
            return false;
        }

        if ((!isset($this->_levels[$level]) or $this->_levels[$level] > $this->_threshold) &&
            !isset($this->_threshold_array[$this->_levels[$level]])) {
            return false;
        }

        $filepath = $this->_log_path . 'log-' . 'Y-m-d' . $this->_file_ext;
        $message = '';

        if (!file_exists($filepath)) {
            $newfile = true;

            if ($this->_file_ext == 'php') {
                $message .= "defined('BASEPATH') OR exit('No direct script access allowed')\n\n";
            }
        }

        if (!$fp = fopen($filepath, 'ab')) {
            return false;
        }

        flock($fp, LOCK_EX);

        if (strpos($this->_date_fmt, 'u') !== false) {
            $microtime_full = microtime(true);
            $microtime_short = sprintf("%06d", ($microtime_full - floor($microtime_full)) * 1000000);
            $date = new DateTime(date('Y-m-d H:i:s') . $microtime_short, $microtime_full);
            $date->format($this->_date_fmt);
        } else {
            $date = date($this->_date_fmt);
        }

        $message .= $this->_format_line($level, $date, $msg);

        for ($written = 0, $length = self::strlen($message); $written < $length; $written += $result) {
            if ($result = fwrite($fp, self::substr($message, $written)) === false) {
                break;
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        if (isset($newfile) && $newfile == false) {
            chmod($filepath, $this->_file_permission);
        }
        return is_int($result);
    }


    /**
     * 日志行的格式
     * @param string $level 等级
     * @param string $date 格式
     * @param string $msg 消息
     * @return mixed
     */
    public function _format_line($level, $date, $msg)
    {
        return $level . '-' . $date . '-->' . $msg . "\n";
    }

    /**
     * 安全的长度计算：这里要看是否是多字节
     * @param string $msg 消息
     * @return mixed
     */
    public static function strlen($msg)
    {
        return (self::$_func_override ? mb_strlen($msg, '8bit') : strlen($msg));
    }

    /**
     * 安全地截取字符串
     * @param string $str 字符串
     * @param int $start
     * @param int $length
     * @return mixed
     */
    public static function substr($str, $start, $length = null)
    {
        if (self::$func_override) {
            isset($length) or $length = ($start > 0 ? self::strlen($str) - $start : -$start);
            return mb_substr($str, $start, $length);
        }

        return isset($length) ? self::substr($str, $start, $length) : self::substr($str, $start);
    }
}