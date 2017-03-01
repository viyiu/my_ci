<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/2/8
 * Time: 17:32
 */
defined("BASEPATH") or exit('no file directly to access');

//处理异常
class CI_Exceptions
{
    //定义ob的level等级
    public $ob_level;

    //错误等级
    public $level = array(
        E_ERROR			=>	'Error',
        E_WARNING		=>	'Warning',
        E_PARSE			=>	'Parsing Error',
        E_NOTICE		=>	'Notice',
        E_CORE_ERROR		=>	'Core Error',
        E_CORE_WARNING		=>	'Core Warning',
        E_COMPILE_ERROR		=>	'Compile Error',
        E_COMPILE_WARNING	=>	'Compile Warning',
        E_USER_ERROR		=>	'User Error',
        E_USER_WARNING		=>	'User Warning',
        E_USER_NOTICE		=>	'User Notice',
        E_STRICT		=>	'Runtime Notice'
    );

    //初始化ob的等级
    public function __construct()
    {
        $this->ob_level = ob_get_level();
    }

    /**
     * 记录异常日志
     * @param string $severity 异常等级
     * @param string $message 消息
     * @param string $filepath 文件位置
     * @param int $line 函数
     */
    public function log_exception($severity, $message, $filepath, $line)
    {
        $severity = isset($this->level[$severity]) ? $this->level[$severity] : $severity;
        log_message($severity, "severity: " . $severity .  "message -->" . $message . 'filepath --> ' . $filepath . 'line:' . $line);
    }

    /**
     * 404找不到消息提示
     * @param string $page 页面
     * @param bool $log_message 是否log的消息
     */
    public function show_404($page, $log_message)
    {
        //是否是客户端还是
        if (is_cli()) {
            $heading = "not found";
            $message = "the controller/method is not found";
        } else {
            $heading = 'the page is not found';
            $message = 'the page is not found';
        }

        //是否记录
        if ($log_message) {
            log_message('error', 'the heading:' . $heading . ', the message:' . $message . ",page:" . $page);
        }

        //输出
        echo $this->show_error($heading, $message, '404_error', 404);
        exit(4);//not found file
    }

    /**
     * 错误消息
     * @param string $heading heading
     * @param string $message 消息
     * @param string $template 模板
     * @param int $status_code 状态吗
     * @return mixed 模板
     */
    public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        //错误模板的路径
        $template_path = config_item('error_view_path');
        if (empty($template_path)) {
            $template_path = VIEWPATH . "errors" . DIRECTORY_SEPARATOR;
        }

        //错误
        if (is_cli()) {
            $message = "\t" . is_array($message) ? implode("\n\t", $message) : $message;
            $template = $template_path  . 'cli' . DIRECTORY_SEPARATOR . $template;
        } else {
            set_status_header($status_code);
            $message = "<p>" . (is_array($message) ? implode("</p><p>", $message) : $message) . "</p>";
            $template = $template . "html" . DIRECTORY_SEPARATOR . $template;
        }

        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_flush();
        }

        ob_start();
        include $template_path . $template . '.php';
        $buffer = ob_get_contents();
        ob_end_flush();
        return $buffer;
    }


    //-------------------------------------------------------------------------------------------

    public function show_exception($exception)
    {

        //错误template的路径
        $template_path = config_item('error_view_path');
        if (empty($template_path)) {
            $template_path = APPPATH . 'errors' . DIRECTORY_SEPARATOR;
        }

        //异常消息
        $message = $exception->getMessage();
        if (empty($message)) {
            $message = '(null)';
        }

        //是否客户端
        if (is_cli()) {
            $template_path .= $template_path . 'cli' . DIRECTORY_SEPARATOR;
        } else {
            $template_path .= $template_path . 'html' . DIRECTORY_SEPARATOR;
        }

        //ob等级
        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_flush();
        }

        //输出内容
        ob_start();
        include $template_path . 'error_exception.php';
        $buffer = ob_get_contents();
        ob_end_clean();
        echo $buffer;
    }

    /**
     * 显示php错误
     * @param string $severity 错误等级
     * @pararm string $message 错误信息
     * @param string $filepath 文件路径
     * @param int $line 文件行数
     */
    public function show_php_error($severity, $message, $filepath, $line)
    {
        //错误模板路径
        $template_path = config_item('error_view_path');
        if (empty($template_path)) {
            $template_path = APPPATH . 'errors';
        }

        //错误等级
        $severity = in_array($severity, $this->level) ? $this->level[$severity] : $severity;

        //是否客户端
        //处于安全，不应该暴露出当前的文件路径
        if (!is_cli()) {
            $filepath = str_replace('\\', '/', $filepath);
            if (false !== strpos($filepath, '/')) {
                $x = explode('/', $filepath);
                $filepath = $x[count($x) - 2] . end($x);
            }
            $template = 'html' . DIRECTORY_SEPARATOR . 'error_php.php';
        } else {
            $template = 'cli' . DIRECTORY_SEPARATOR . 'error_php.php';
        }

        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_flush();
        }

        ob_start();
        include $template_path . $template;
        $buffer = ob_get_contents();
        ob_end_clean();
        echo $buffer;
    }
}