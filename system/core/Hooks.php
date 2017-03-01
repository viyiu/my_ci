<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/2/14
 * Time: 16:53
 */
defined('BASEPATh') or exit('no direct script access allowed');

class CI_Hooks
{
    //是否可以钩
    public $enable = false;

    //config/hooks.php的数组
    public $hooks = array();

    //使用钩子方法的类
    protected $_object = array();

    //使用在进程中
    protected $_in_progress = false;

    //初始化
    public function __construct()
    {
        $CFG = &load_class('Config', 'core');
        log_message('info', 'the config intinialize');

        if ($CFG->item['enable_hooks'] === false) {
            return;
        }

        if (file_exists(APPPATH . 'config/hooks.php')) {
            include_once APPPATH . 'config/hooks.php';
        }

        if (file_exists(APPPATH . 'config/' . ENVIRONMENT . '/hooks.php')) {
            include_once APPPATH . 'config/' . ENVIRONMENT . '/hooks.php';
        }

        if (!isset($hook) or ! is_array($hook)) {
            return;
        }

        $this->hooks = &$hook;
        $this->enable = true;
    }

    /**
     * 调用钩子
     * @param string $which
     * @return mixed
     */
    public function call_hook($which = '')
    {
        if (!$this->enable or !isset($this->hooks[$which])) {
            return false;
        }

        if (is_array($this->hooks[$which]) && !isset($this->hooks[$which]['function'])) {
            foreach ($this->hooks[$which] as $key => $val) {
                $this->_run_hook($val);
            }
        } else {
            $this->_run_hook($this->hooks[$which]);
        }
        return true;
    }

    /**
     * 调用具体的钩子
     * @param array $data
     * @return mixed
     */
    protected function _run_hook($data)
    {
        if (is_callable($data)) {
            is_array($data) ? $data[0]->$data[1] : $data();
            return true;
        } elseif (!is_array($data)) {
            return false;
        }

        if ($this->_in_progress === true) {
            return ;
        }

        if (!isset($data['filepath'], $data['filename'])) {
            return false;
        }

        $path = APPPATH . $data['filepath'] .'/'. $data['filename'];
        if (!file_exists($path)) {
            return false;
        }

        $class = empty($data['class']) ? false : $data['class'];
        $function = empty($data['function']) ? false : $data['function'];
        $param = isset($data['params']) ? $data['params'] : '';
        if (empty($class)) {
            return false;
        }

        $this->_in_progress = true;

        if ($class !== false) {
            if (isset($this->_object[$class])) {
                if (method_exists($this->_object[$class], $function)) {
                    $this->_object[$class]->$function($param);
                } else {
                    $this->_in_progress = false;
                }
            } else {
                class_exists($class, false) or include_once $path;
                if (! class_exists($class, false) or !method_exists($class, $function)) {
                    $this->_in_progress = false;
                }
                $this->_object[$class] = new $class();
                $this->_object[$class]->$function($param);
            }
        } else {
            function_exists($function) or require_once $path;
            if (!function_exists($function)) {
                $this->_in_progress = false;
            }
            $function($param);
        }
        $this->_in_progress = false;
        return true;
    }
}