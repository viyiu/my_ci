<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/2/25
 * Time: 16:43
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class CI_Router
{
    //ci的config对象
    public $config;

    //路由列表
    public $routes = array();

    //当前类的名字
    public $class = '';

    //方法列表
    public $method = 'index';

    //子文件夹包含请求的控制器
    public $directory = '';

    //默认控制器
    public $default_controller;

    //是否转化控制器的斜线:在url中，以前是不支持破折号在类名中的，例如：my-controller
    //现在这个设置为tru，那么就可以自动转化为my_controller
    public $translate_uri_dashes = false;

    //是否可以使用请求字符串
    public $enable_query_strings = false;

    //初始化
    public function __construct($routing = null)
    {
        $this->config = & load_class('Config', 'core');
        $this->routes = & load_class('URI', 'core');
        $this->enable_query_strings = (!is_cli() && $this->config->item('enable_query_strings') === true);

        is_array($routing) && isset($routing['directory']) && $this->set_directory($routing['directory']);
        $this->_set_routing();

        if (is_array($routing)) {
            empty($routing['class']) || $this->set_class($routing['class']);
            empty($routing['function']) || $this->set_method($routing['function']);
        }

        log_message('info', 'route is initle');
    }

    /**
     * 设置路由
     */
    protected function _set_routing()
    {
        if (file_exists(APPPATH . 'config/config.php')) {
            include_once APPPATH . 'config/config.php';
        }

        if (file_exists(APPPATH . 'config/' . ENVIRONMENT . '/config.php')) {
            include_once APPPATH . 'config/' . ENVIRONMENT . '/config.php';
        }

        if (isset($route) && is_array($route)) {
            isset($route['default_controller']) && $this->default_controller = $route['default_controller'];
            isset($route['translate_uri_dashes']) && $this->translate_uri_dashes = $route['translate_uri_dashes'];
            unset($route['default_controller'], $route['translate_uri_dashes']);
            $this->routes = $route;
        }

        if ($this->enable_query_strings) {
            if (!isset($this->directory)) {
                $_d = $this->config->item('directory_trigger');
                $_d = isset($_GET[$_d]) ? trim($_GET[$_d], "\t\n\r\0\x0B/") : '';

                if ($_d != '') {
                    $this->uri->filter_uri($_d);
                    $this->set_directory($_d);
                }
            }

            $_c = $this->config->item('controller_trigger');
            if (!empty($_GET[$_c])) {
                $this->uri->filter_uri($_GET[$_c]);
                $this->set_class($_GET[$_c]);

                $_f = $this->config->item('function_trigger');
                if (!empty($_GET[$_f])) {
                    $this->uri->filter_uri($_f);
                    $this->set_method($_GET[$_f]);
                }

                $this->config->rsegment = array(
                    '1' => $_GET[$_c],
                    '2' => $_GET[$_f]
                );
            } else {
                $this->_set_default_controller();
            }
            return ;
        }

        if ($this->uri->uri_string != '') {
            $this->_parse_routes();
        } else {
            $this->_set_default_controller();
        }
    }

    /**
     * 设置请求路由
     * @param array $segments 路由参数
     * @return void
     */
    protected function _set_request($segments = array())
    {
        $this->_validate_request($segments);

        if (empty($segments)) {
            $this->_set_default_controller();
            return ;
        }

        if ($this->translate_uri_dashes == true) {
            $segments[0] = str_replace('-', '_', $segments[0]);
            if (isset($segments[1])) {
                $segments[1] = str_replace('-', '_', $segments[1]);
            }
        }

        $this->set_class($segments[0]);
        if (isset($segments[1])) {
            $this->method($segments[1]);
        } else {
            $segments[1] = 'index';
        }

        array_unshift($segments, null);
        unset($segments[0]);
        $this->uri->rsegments = $segments;
    }

    /**
     * 验证路由参数
     * @param array $segments 路由参数
     * @return mixed
     */
    protected function _validate_request($segments)
    {
        $c = count($segments);
        $directory_override = isset($this->directory);

        while ($c-- > 0) {
            $test = $this->directory .
                ucfirst($this->translate_uri_dashes === true ? str_replace('-', '_', $segments[0]) : $segments[0]);
            if (!file_exists(APPPATH . 'controllers/' . $test . '.php')
                && $directory_override == true
                && is_dir(APPPATH . 'controllers/' . $test . $segments[0])
            ) {
                $this->set_directory($segments[0], true);
                continue;
            }

            return $segments;
        }

        return $segments;
    }

    /**
     * 设置默认控制器
     * @return void
     */
    protected function _set_default_controller()
    {

        if (empty($this->default_controller)) {
            log_message('error', 'there is no default controller');
        }

        //确定方法是不是指定了
        if (sscanf($this->default_controller, '/%[^/]%s/', $class, $method) !== 2) {
            $method = 'index';
        }

        //查看文件是否存在
        if (!file_exists(APPPATH . 'controllers/' . $this->directory . '/' . $class . '.php')) {
            return;
        }

        $this->set_class($class);
        $this->set_method($method);

        $this->uri->rsegments = array(
            '1' => $class,
            '2' => $method
        );

        log_message('debug', 'the default controller is set');
    }

    /**
     * 匹配路由
     * 主要是跟配置文件里面的配置选项匹配
     */
    public function _parse_routes()
    {
        $uri = implode('/', $this->uri->segments);

        //请求方法
        $http_verb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';

        //循环配置的东西匹配
        foreach ($this->routes as $key => $val) {
            //查看是否是get post put 等方法
            if (is_array($val)) {
                $val = array_change_key_case($val, CASE_LOWER);
                if (isset($val[$http_verb])) {
                    $val = $val[$http_verb];
                } else {
                    continue;
                }
            }

            //匹配
            $key = str_replace(array(':nay', ':num'), array('[^/]', '[0-9]'), $key);

            //
            if (preg_match('#'.$key.'#', $uri, $matches)) {
                //是否使用了回掉函数
                if (!is_string($matches) && is_callable($val)) {
                    array_shift($matches);
                    $val = call_user_func_array($val, $matches);
                } elseif (strpos($val, '$') !== false && strpos($key, '(') !== false) {
                    $val = preg_replace('#^' . $key . '$#', $val, $uri);
                }
                $this->_set_request(explode('/', $val));
                return ;
            }

            $this->_set_request(explode('/', $val));
            return ;
        }
    }

    /**
     * 设置类名
     * @param string $class 类名
     * @return void
     */
    public function set_class($class)
    {
        $this->class = str_replace(array('/', '.'), '', $class);
    }

    /**
     * 导出当前方法
     * @deprecated
     * @return string
     */
    public function fetch_class()
    {
        return $this->class;
    }

    /**
     * 设置当前方法
     * @param string $method
     */
    public function set_method($method)
    {
        $this->method = $method;
    }

    /**
     * 设置文件夹
     * @param string $dir 文件夹
     * @param bool
     */
    public function set_directory($dir, $append = false)
    {
        if ($append == true or empty($this->directory)) {
            $this->directory = str_replace('.', '', trim($dir, '/')) . '/';
        } else {
            $this->directory .= str_replace('.', '', trim($dir, '/')) . '/';
        }
    }
}
