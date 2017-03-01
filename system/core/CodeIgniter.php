<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/1/4
 * Time: 15:53
 */
defined('BASEPATH') or exit('no exit script to access');

//定义ci的版本
const CI_VERSION = '3.1.2';

//加载常量内容
if (file_exists(APPPATH . 'config/' . ENVIRONMENT . 'constants.php')) {
    require_once APPPATH . 'config/' . ENVIRONMENT . 'constants.php';
}
require_once APPPATH . 'config/constants.php';

//加载全局函数
require_once BASEPATH . 'core/Common.php';

//安全设置
if (!is_php('5.4')) {
    ini_set('magic_quotes_runtime', 0);

    if ((bool)ini_get('register_globals')) {
        $_protected = array(
            '_SERVER',
            '_GET',
            '_POST',
            '_FILES',
            '_REQUEST',
            '_SESSION',
            '_ENV',
            '_COOKIE',
            'GLOBALS',
            'HTTP_RAW_POST_DATA',
            'system_path',
            'application_folder',
            'view_folder',
            '_protected',
            '_registered'
        );

        $_registered = ini_get('variables_order');
        foreach (array('E' => '_ENV', 'G' => '_GET', 'P' => '_POST', 'C' => '_COOKIE', 'S' => '_SESSION') as $key => $superglobal) {
            if (strpos($_registered, $key) === false) {
                continue;
            }

            foreach (array_keys($$superglobal) as $var) {
                if (isset($GLOBALS[$var]) && ! in_array($_protected, $var, true)) {
                    $GLOBALS[$var] = null;
                }
            }
        }
    }
}

//手动设置处理错误/异常/shutdown函数
set_error_handler('_error_handler');
set_exception_handler('_exception_handler');
register_shutdown_function('_shutdown_function');

//设置类的下标
if (!empty($assign_to_config['subclass_prefix'])) {
    get_config(array('subclass_prefix' => $assign_to_config['subclass_prefix']));
}

//是否使用 composer autoload
if ($composer_autoload = config_item('composer_autoload')) {
    if ($composer_autoload == true) {
        file_exists(APPPATH . 'vendor/autoload.php') ?
            include APPPATH . 'vendor/autoload.php'
            : log_message('error', 'the composer autoload '.APPPATH . 'vendor/autoload.php is not found\\n');
    } elseif (file_exists($composer_autoload)) {
        include $composer_autoload;
    } else {
        log_message('error', 'the composer_autoload config is not right\\n');
    }
}

//测试基准点
$BM = &load_class('Benchmark', 'core');
$BM->marker('total_execution_time_start');
$BM->marker('loading_time:_base_classes_start');

//加载钩子程序
$EXT = & load_class('Hooks', 'core');

//是否先于系统调用的钩子
$EXT->call_hook('pre_system');

//加载核心配置
$CFG = &load_class("Config", 'core');
if (isset($assign_to_config) && is_array($assign_to_config)) {
    foreach ($assign_to_config as $key => $value) {
        $CFG->set_item($key, $value);
    }
}

//设置字符集
$charset = strtolower(config_item('charset'));
ini_set('default_charset', $charset);
if (extension_loaded('mbstring')) {
    define("MB_ENABLE", true);
    @ini_set('mbstring.internal_encoding', $charset);
    mb_substitute_character('none');
} else {
    define('MB_ENABLE', false);
}

if (extension_loaded('iconv')) {
    define('ICONV_ENABLE', true);
    @ini_set('iconv.internal_encoding', $charset);
} else {
    define('ICONV_ENABLE', false);
}

if (is_php('5.6')) {
    ini_set('php.internal_encoding', $charset);
}

//加载兼容性特性
require BASEPATH . 'core/compat/mbstring.php';
require BASEPATH . 'core/compat/hash.php';
require BASEPATH . 'core/compat/standard.php';
require BASEPATH . 'core/compat/password.php';

//加载utf8
$UNI = &load_class('Utf8', 'core');

//加载路由
$RTR = @load_class('Router', 'core', isset($routing) ? $routing : null);
