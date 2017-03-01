<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/2/15
 * Time: 10:11
 */
defined('BASEPATH') or exit('no direct file to access!');

class CI_Config
{
    //所有的配置
    public $config = array();

    //已经加载的配置
    public $is_loaded = array();

    //可以加载的路径
    public $_config_paths = array(APPPATH);

    //初始化:设置base_url
    public function __construct()
    {
        $this->config = &get_config();

        if (empty($this->config['base_url'])) {
            if (isset($_SERVER['SERVER_ADDR'])) {
                if (strpos($_SERVER['SERVER_ADDR'], ':') !== false) {
                    $server_addr = '[' . $_SERVER['SERVER_ADDR'] . ']';
                } else {
                    $server_addr = $_SERVER['SERVER_ADDR'];
                }
                $base_url = (is_https() ? 'https://' : 'http://') . $server_addr .
                    substr($_SERVER['SCRIPT_NAME'], 0, strpos($_SERVER['SCRIPT_NAME'], basename($_SERVER['SCRIPT_FILENAME'])));
            } else {
                $base_url = "http://localhost/";
            }
            $this->set_item('base_url', $base_url);
        }

        log_message('info', 'config is initialize');
    }

    /**
     * 加载配置文件
     * @param string $file 配置文件名字
     * @param bool $user_sections 配置文件的值是否加载
     * @param bool $fail_gracefully 是否返回错误
     * @return bool
     */
    public function load($file, $user_sections = false, $fail_gracefully = false)
    {
        $file = ($file === '') ? 'config' : str_replace('.php', '', $file);
        $loaded = false;

        foreach ($this->_config_paths as $path) {
            foreach (array($file, ENVIRONMENT . DIRECTORY_SEPARATOR . $file) as $location) {
                $file_path = $path . 'config/' . $location . '.php';

                if (in_array($file_path, $this->is_loaded, true)) {
                    return true;
                }

                if (!file_exists($file_path)) {
                    continue;
                }

                include $file_path;

                if (!isset($config) && !is_array($config)) {
                    if ($fail_gracefully == true) {
                        return false;
                    }
                    log_message('error', 'the file '.$file_path.'is not found');
                }

                if ($user_sections == true) {
                    $this->config[$file] = isset($this->config[$file]) ? array_merge($this->config[$file], $config) : $config;
                } else {
                    $this->config = array_merge($this->config, $config);
                }

                $this->is_loaded[] = $file_path;
                $config = null;
                $config = true;
                log_message('debug', 'the config file is loaded:'.$file);
            }
        }
        if ($loaded == true) {
            return true;
        } elseif ($fail_gracefully == true) {
            return false;
        }
        show_error('the config file '.$file_path.'is not exist');
    }

    /**
     * 查看一个配置
     * @param string $item
     * @param string $index
     * @return mixed
     */
    public function item($item, $index = '')
    {
        if ($index == '') {
            return isset($this->config[$item]) ? $this->config[$item] : null;
        }

        return isset($this->config[$index],$this->config[$index][$item]) ? $this->config[$index][$item] : null;
    }

    /**
     * 返回一个配置值，配置值后面加斜线
     * @param string $item
     * @return mixed
     */
    public function slash_item($item)
    {
        if (!isset($this->config[$item])) {
            return null;
        } elseif (trim($this->config[$item]) == '') {
            return '';
        }

        return rtrim($this->config[$item], '/') . '/';
    }

    /**
     * 网页url
     * @param string|string[] $uri
     * @param string $protocol
     * @return string
     */
    public function site_url($uri, $protocol = null)
    {
        $base_url = $this->slash_item('base_url');

        if (isset($protocol)) {
            if ($protocol =='') {
                $base_url = substr($base_url, strpos($base_url, '//'));
            } else {
                $base_url = $protocol . substr($base_url, strpos($base_url, '://'));
            }
        }

        if (empty($uri)) {
            return $base_url . $this->item('index_page');
        }

        $uri = $this->_uri_string($uri);

        if ($this->item('enable_query_uri') === false) {
            $suffix = isset($this->config['url_suffix']) ? $this->config['url_suffix'] : '';

            if ($suffix != '') {
                if (($offset = strpos($uri, '?')) !== false) {
                    $uri = substr($uri, 0, $offset) . $uri . substr($uri, $offset);
                } else {
                    $uri .= $suffix;
                }
            }

            return $base_url . $this->item('index_page').$uri;
        } elseif (strpos($uri, '?')) {
            $uri = "?" . $uri;
        }

        return $base_url . $this->item('index_page') . $uri;

    }

    /**
     * 网页基本的url
     * @param string|string[] $uri
     * @param string $protocol
     * @return string
     */
    public function base_url($uri, $protocol = null)
    {
        $base_url = $this->slash_item('base_url');

        if (isset($protocol)) {
            if ($protocol =='') {
                $base_url = substr($base_url, strpos($base_url, '//'));
            } else {
                $base_url = $protocol . substr($base_url, strpos($base_url, '://'));
            }
        }
        return $base_url . $uri;
    }

    /**
     * url字符
     * @param string|string[] $uri urine字符串或者字符串数组
     * @return string
     */
    public function _uri_string($uri)
    {
        if ($this->item('enable_query_strings') === false) {
            is_array($uri) && $uri = implode('/', $uri);
            return ltrim($uri, '/');
        } elseif (is_array($uri)) {
            return http_build_query($uri);
        }

        return $uri;
    }

    /**
     * 设置配置项
     * @param string $item 配置项键值
     * @param mixed $value 配置的值
     */
    public function set_item($item, $value)
    {
        $this->config($item, $value);
    }
}