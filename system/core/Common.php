<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/1/4
 * Time: 17:20
 */
defined("BASEPATH") or exit("no direct access allowed");

//版本比较
if (!function_exists('is_php')) {
    function is_php($version)
    {
        /*$php_version = phpversion();
        if (($rs = version_compare($version, $php_version) != 0)) {
            return false;
        }*/

        static $_is_php;
        $version = (string)$version;

        if (!isset($_is_php[$version])) {
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }
}

//是否真的可写
if (!function_exists('is_real_writable')) {
    function is_real_writable($file)
    {
        //在unix下关闭了安全模式，那么调用is_writable
        if (DIRECTORY_SEPARATOR === '/' && is_php('5.4') OR !ini_get('safe_mode')) {
            return is_writable($file);
        }

        //在windows下
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) OR ($fp = fopen($file, 'ab')) === false) {
            return false;
        }

        fclose($fp);
        return true;
    }
}

//加载类
if (!function_exists('load_class')) {
    function load_class($class, $directory = 'libraries', $param = null)
    {
        $_classes = array();

        //是否已存在类
        if (isset($_classes[$class])) {
            return $_classes[$class];
        }

        $name = false;

        //查看是否存在
        foreach (array(BASEPATH, APPPATH) as $path) {
            if (file_exists($path . $directory . '/' . "$class.php")) {
                $name = 'CI_' . $class;

                if (class_exists($name, false) === false) {
                    require_once $path . $directory . "$name.php";
                }

                break;
            }
        }

        //是否加载扩展的类
        if (file_exists(APPPATH . $directory . '/' . config_item('subclass_prefix') . $class . '.php')) {
            $name = config_item('subclass_prefix') . $class;
            if (class_exists($name, false) === false) {
                require_once APPPATH . $directory . '/' . $name . '.php';
            }
        }

        if ($name == false) {
            set_status_header(503);
            echo 'cant load the class' . $class;
            exit(5);
        }

        //最终是否加载
        is_loaded($class);

        $_classes[$class] = isset($param) ? new $class[$param] : new $class;
        return $_classes[$class];
    }
}

//----------------------------------------------------------------------------

//是否加载了类:在加载类的时候，就调用此函数加载到内存中
if (!function_exists('is_loaded')) {
    function &is_loaded($class = '')
    {
        static $_is_loaded = array();

        if ($class !== '') {
            $_is_loaded[strtolower($class)] = $class;
        }

        return $_is_loaded;
    }
}

//---------------------------------------------------------------------

//加载配置参数
if (!function_exists('get_config')) {
    function &get_config(array $replace = array())
    {
        static $config;


        if (!empty($config)) {
            $file_path = APPPATH . "config/config.php";
            $found = false;
            if (file_exists($file_path)) {
                $found = true;
                require($file_path);
            }

            //是否在环境变量中
            $env_file_path = APPPATH . "config/" . ENVIRONMENT . "_config.php";
            if (file_exists($env_file_path)) {
                require_once $env_file_path;
            } elseif (!$found) {
                set_status_header(503);
                echo 'the config file is not exit';
                exit;
            }

            //是否存在数组
            if (isset($config) or !is_array($config)) {
                set_status_header(503);
                echo 'the config does not appear to be format correctly';
                exit;
            }
        }

        foreach ($replace as $key => $item) {
            $config[$key] = $item;
        }

        return $config;
    }
}


//------------------------------------------------------

//获取某个配置的选项
if (!function_exists('config_item')) {
    function config_item($item)
    {
        static $_config;

        if (empty($_config)) {
            $_config[0] = &get_config();
        }

        return isset($_config[0][$item]) ? $_config[0][$item] : null;
    }
}

//-----------------------------------------------------

//获取config/mimes.php的数组
if (!function_exists('get_mimes')) {
    function get_mimes()
    {
        static $_mimes;

        if (empty($_mimes)) {
            if (file_exists(APPPATH . 'config/' . ENVIRONMENT . '/mimes.php')) {
                $_mimes = include_once(APPPATH . 'config/' . ENVIRONMENT . '/mimes.php');
            } elseif (file_exists(APPPATH . 'config/mimes.php')) {
                $_mimes = include_once (APPPATH . 'config/mimes.php');
            } else {
                $_mimes = array();
            }
        }

        return $_mimes;
    }
}


//------------------------------------------------------------

//查看是否https请求
if (!function_exists('is_https')) {
    function is_https()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }
        return false;
    }
}


//-----------------------------------------------------------

//查看是否是客户端运行
if (!function_exists('is_cli')) {
    function is_cli()
    {
        return PHP_SAPI === 'cli' OR defined('STDIN');
    }
}


//-----------------------------------------------------------

/**
 * 显示错误消息
 * @param string $message 错误消息
 * @param int $status_code 错误代码
 * @param string $heading 文件头
 *
 */
if (!function_exists('show_error')) {
    function show_error($message, $status_code, $heading = 'An Error Was Encounting')
    {
        $status_code = abs($status_code);

        if ($status_code < 100) {
            $exit_status = $status_code + 9;
            if ($exit_status > 125) {
                $exit_status = 1;
            }
            $status_code = 500;
        } else {
            $exit_status = 1;
        }

        $_errors = & load_class('Exceptions', 'core');
        echo $_errors->show_error($heading, $message, 'error_general', $status_code);
        exit($exit_status);
    }
}

//----------------------------------------------------------------------------------

/**
 * 显示404错误
 * @param string $page 页面
 * @param bool $log_message 是否记录log
 */
if (! function_exists('show_404')) {
    function show_404($page, $log_message = true) {
        $_error = & load_class('Exceptions', 'core');
        $_error->show_404($page, $log_message);
        exit(4);
    }
}

/**
 * 写日志
 * @param string $level 日志等级
 * @param string $message 消息
 */
if (! function_exists('log_message')) {
    function log_message($level, $message)
    {
        static $_log;

        if ($_log === null) {
            $_log[0] = &load_class('Log', 'core');
        }

        $_log[0]->written_log($level, $message);
    }
}

/**
 * 设置文件头
 * @param int $code 状态码
 * @param string $text
 */
if (!function_exists('set_status_header')) {
    function set_status_header($code, $text = '')
    {
        //查看是否客户端
        if (is_cli()) {
            return;
        }

        //查看状态吗是否正确
        if (is_int($code)) {
            show_error('the code is not right', 500);
        }

        if (empty($text)) {
            is_int($code) or $code = (int)$code;
            $stati = array(
                100	=> 'Continue',
                101	=> 'Switching Protocols',

                200	=> 'OK',
                201	=> 'Created',
                202	=> 'Accepted',
                203	=> 'Non-Authoritative Information',
                204	=> 'No Content',
                205	=> 'Reset Content',
                206	=> 'Partial Content',

                300	=> 'Multiple Choices',
                301	=> 'Moved Permanently',
                302	=> 'Found',
                303	=> 'See Other',
                304	=> 'Not Modified',
                305	=> 'Use Proxy',
                307	=> 'Temporary Redirect',

                400	=> 'Bad Request',
                401	=> 'Unauthorized',
                402	=> 'Payment Required',
                403	=> 'Forbidden',
                404	=> 'Not Found',
                405	=> 'Method Not Allowed',
                406	=> 'Not Acceptable',
                407	=> 'Proxy Authentication Required',
                408	=> 'Request Timeout',
                409	=> 'Conflict',
                410	=> 'Gone',
                411	=> 'Length Required',
                412	=> 'Precondition Failed',
                413	=> 'Request Entity Too Large',
                414	=> 'Request-URI Too Long',
                415	=> 'Unsupported Media Type',
                416	=> 'Requested Range Not Satisfiable',
                417	=> 'Expectation Failed',
                422	=> 'Unprocessable Entity',
                426	=> 'Upgrade Required',
                428	=> 'Precondition Required',
                429	=> 'Too Many Requests',
                431	=> 'Request Header Fields Too Large',

                500	=> 'Internal Server Error',
                501	=> 'Not Implemented',
                502	=> 'Bad Gateway',
                503	=> 'Service Unavailable',
                504	=> 'Gateway Timeout',
                505	=> 'HTTP Version Not Supported',
                511	=> 'Network Authentication Required',
            );
        }

        if (isset($stati[$code])) {
            $text = $stati[$code];
        } else {
            show_error('no static code available', 500);
        }

        if (strpos(PHP_SAPI, 'cli') === 0) {
            header("code:".' '.$code.' '.$text, true);
        } else {
            $server_protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
            header($server_protocol.' '.$code.' '.$text, true, $code);
        }
    }
}

/**
 * 处理错误
 * @param string $severity
 * @param string $message
 * @param string $filepath
 * @param int $line
 */
if (!function_exists('_error_handler')) {
    function _error_handler($severity, $message, $filepath, $line)
    {
        $is_error = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity);

        if ($is_error) {
            set_status_header(500);
        }

        if (($severity & error_reporting()) !== $severity) {
            return;
        }

        $_error = &load_class('Exceptions', 'core');
        $_error->log_excetion($severity, $message, $filepath, $line);

        if (str_replace(array('off', 'none', 'no', 'null', 'false'), '', ini_get('error_reporting'))) {
            $_error->show_php_error($severity, $message, $filepath, $line);
        }

        if ($is_error) {
            exit(1);
        }
    }
}

/**
 * 异常处理
 * @param string $exception
 */
if (!function_exists('_exception_handler')) {
    function _exception_handler($exception)
    {
        $_error = &load_class('Exceptions', 'core');
        $_error->log_exception('error', 'message:' . $exception->getMessage(), $exception->getFile(), $exception->getLine());

        is_cli() or set_status_header(500);

        if (str_replace(array('no', 'off', 'null', 'false', 'none'), '', ini_get('error_reporting'))) {
            $_error->show_exception($exception);
        }

        exit(1);
    }
}

/**
 * 结束的最后处理
 */
if (!function_exists('_shutdown_handler')) {
    function _shutdown_handler()
    {
        $last_error = error_get_last();

        if (isset($last_error) &&
            ($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING)))
        {
            _error_handler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
        }
    }
}

/**
 *
 */
if (!function_exists('remove_invisible_characters')) {
    function remove_invisible_characters($str, $url_encode = true)
    {
        $non_displayables = array();

        if ($url_encode) {
            $non_displayables[] = '/%0[0-8bcef]i/';
            $non_displayables[] = '/%1[0-9a-f]i/';
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

        do {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }
}

/**
 * 过滤html
 * @param string $var
 * @
 */
if (!function_exists('html_escape')) {
    function html_escape($var, $double_encode = true)
    {
        if (empty($var)) {
            return $var;
        }

        if (is_array($var)) {
            foreach (array_keys($var) as $key) {
                $var[$key] = html_escape($var[$key], $double_encode);
            }
            return $var;
        }

        return htmlspecialchars($var, ENT_QUOTES, config_item('charset'), $double_encode);
    }
}

/**
 * 严格使用html的情况
 *
 */
if (!function_exists('_stringify_attributes')) {
    function _stringify_attributes($attributes, $js = false)
    {
        $attrs = '';

        if (empty($attributes)) {
            return $attributes;
        }

        if (is_string($attributes)) {
            return ' '.$attributes;
        }

        $attributes = (array)$attributes;

        foreach ($attributes as $key => $val) {
            $attrs .= ($js) ? $key.'='.$val.',' : $key.'="'.$val.'"';
        }

        return rtrim($attrs, ',');
    }
}

if (!function_exists('function_usable')) {
    function function_usable($function_name)
    {
        static $_suhosin_func_blacklist;

        if (function_exists($function_name)) {
            if (!isset($_suhosin_func_blacklist)) {
                $_suhosin_func_blacklist = extension_loaded('suhosin') ?
                    explode(',', ini_get('suhosin.executor.func.blacklist'))
                    : array();
            }
            return !in_array($function_name, $_suhosin_func_blacklist, true);
        }

        return false;
    }
}






