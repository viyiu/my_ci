<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2016/12/27
 * Time: 17:46
 */
//确定环境
define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development');

//不同的环境的报错形式
switch (ENVIRONMENT) {
    case 'development':
        error_reporting(-1);
        ini_set('display_errors', 1);
        break;
    case 'testing':
    case 'production':
        ini_set('display_errors', 0);
        if (version_compare(PHP_VERSION, '5.3', '>=')) {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
        }
        break;
    default:
        header('HTTP/1.1 503 the server unavailable', true, 503);
        echo 'the application environment is not set correctly';
        exit(1);
}

//system 所在文件未知的常量
$system_path = 'system';

//application文件夹
$application_folder = 'application';

//view文件夹的folder
$view_folder = '';

//如果使用了cli，那么设置正确的目录位置
if (defined('STDIN')) {
    chdir(dirname(__FILE__));
}

if (($_temp = realpath($system_path)) !== false) {
    $system_path = $_temp . DIRECTORY_SEPARATOR;
} else {
    $system_path = strtr(rtrim($_temp, '/\\'), '/\\', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR;
}

if (!is_dir($system_path)) {
    header('HTTP/1.1 503 the server unavailable', true, 503);
    echo "your system path is not set correctly:" . pathinfo(__FILE__, PATHINFO_BASENAME);
    exit(3);
}

//定义文件夹
//当前文件夹
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
//system文件夹
define('BASEPATH', $system_path);
//当前控制器的文件夹
define('FCDIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
//system的文件夹
define('SYSDIR', dirname(BASEPATH));

//定义application文件夹
if (is_dir($application_folder)) {
    if (($_temp = realpath($application_folder)) !== false) {
        $application_folder = $_temp . DIRECTORY_SEPARATOR;
    } else {
        $application_folder = strtr(rtrim($_temp, '/\\'), '/\\', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
    }
} elseif (is_dir(BASEPATH . $application_folder . DIRECTORY_SEPARATOR)) {
    $application_folder = BASEPATH . strtr(trim($application_folder, '/\\'), '/\\')
        . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR;
} else {
    header('HTTP/1.1 503 the server unavailable', true, 503);
    echo "your application path is not set correctly:" . SELF;
    exit(3);
}

//定义application文件夹
define('APPPATH', $application_folder . DIRECTORY_SEPARATOR);

//view文件夹
if (!isset($view_folder[0]) && is_dir(APPPATH . 'views' . DIRECTORY_SEPARATOR)) {
    $view_folder = APPPATH . 'views';
} elseif (is_dir($view_folder)) {
    if (($_temp = realpath($view_folder)) !== false) {
        $view_folder = $_temp;
    } else {
        $view_folder = strtr(trim($view_folder, '/\\'), '/\\', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
    }
} elseif (is_dir(APPPATH . $view_folder . DIRECTORY_SEPARATOR)) {
    $view_folder = APPPATH . trim(trim($view_folder . '/\\'), '/\\', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
} else {
    header('HTTP/1.1 503 the server unavailable', true, 503);
    echo "your view path is not set correctly:" . SELF;
    exit(3);
}

define('VIEWPATH', $view_folder . DIRECTORY_SEPARATOR);

require_once BASEPATH . '/core/CodeIgniter.php';