<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/2/23
 * Time: 15:50
 */
defined('BASEPATH') or exit('no script to access allowed');

class CI_URI
{
    //缓存的键值
    public $keyval = array();

    //当前的uri字符
    public $uri_string = '';

    //路由数组
    public $rsegments = array();

    //uri参数数组：从1开始
    public $segments = array();

    //允许的参数数组
    protected $_permitted_uri_chars;

    /**
     * 初始化
     */
    public function __construct()
    {
        $this->config = &load_class('Config', 'core');

        if (is_cli() or $this->config->item('enable_query_strings') !== true) {
            $this->_permitted_uri_chars = $this->config->item('permitted_uri_chars');
        }

        if (is_cli()) {
            $this->_parse_argv();
        } else {
            $protocol = $this->config->item('uri_protocol');
            empty($protocol) or $protocol = "REQUEST_URI";

            switch ($protocol) {
                case "REQUEST_URI":
                    $this->_parse_request_uri();
                    break;
                case "REQUEST_STRING":
                    $this->_parse_query_string();
                    break;
                case "PATH_INFO":
                default:
                    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] :
                        $this->_parse_request_uri();
                    break;
            }
        }
        $this->_set_uri_string($uri);
    }

    /**
     * 设置字符串
     * @param string $uri
     */
    protected function _set_uri_string($uri)
    {
        //过滤字符串
        $this->uri_string = trim(remove_invisible_characters($uri, false), '/');

        if ($this->uri_string !== '') {
            //是否有前缀
            if (($suffix = (string)$this->config->item('url_suffix')) !== '') {
                $slen = strlen($suffix);
                if (substr($this->uri_string, -$slen) === $suffix) {
                    $this->uri_string = substr($this->uri_string, 0, -$slen);
                }
            }

            $this->segments[0] = null;
            foreach (explode('/', trim($this->uri_string, '/')) as $val) {
                $val = trim($val);
                $this->filter_uri($val);
                if ($val !== '') {
                    $this->segments[] = $val;
                }
            }
            unset($this->segments[0]);
        }
    }

    /**
     * 解析字符串
     * 自动解析字符串
     */
    protected function _parse_query_string()
    {
        $uri = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');

        if (trim($uri, '/') == '') {
            return '';
        } elseif (strncmp($uri, '/', 1) === 0) {
            $uri = explode('/', $uri, 2);
            $_SERVER['REQUEST_STRING'] = isset($uri[0]) ? $uri[0] : '';
            $uri = $uri[0];
        }

        parse_str($_SERVER['REQUEST_STRING'], $_GET);
        return $this->_remove_relative_directory($uri);
    }

    /**
     * 解析请求uri
     * @return string
     */
    protected function _parse_request_uri()
    {
        if (!isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'])) {
            return '';
        }

        $uri = parse_url("http://dummy" . $_SERVER['REQUEST_URI']);
        $query = isset($uri['query']) ? $uri['query'] : '';
        $uri = isset($uri['path']) ? $uri['path'] : '';

        if (isset($_SERVER['SCRIPT_NAME'][0])) {
            if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
                $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
            } elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
                $uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
            }
        }

        if (trim($uri, '/') && strncmp($query, '/', 1) === 0) {
            $query = explode("?", $query, 2);
            $uri = $query[0];
            $_SERVER['QUERY_STRING'] = isset($query[1]) ? $query[1] : '';
        } else {
            $_SERVER['QUERY_STRING'] = $query;
        }

        parse_str($_SERVER['QUERY_STRING'], $_GET);
        if ($uri === '/' or $uri === '') {
            return '/';
        }

        return $this->_remove_relative_directory($uri);
    }

    /**
     * 移除相对路径和多余的///
     * @param string $uri uri
     * @return string
     */
    public function _remove_relative_directory($uri)
    {
        $uris = array();
        $tok = strtok($uri, '/');
        while ($tok !== false) {
            if ((!empty($tok) or $tok === '0') && $tok !== '..') {
                $uris[] = $tok;
            }
            $tok = strtok('/');
        }
        return implode('/', $uris);
    }

    /**
     * 解析客户端的参数
     * 将每一行命令当作是参数解析
     * @return string
     */
    protected function _parse_argv()
    {
        $args = array_slice($_SERVER['argv'], 1);
        return $args ? implode('/', $args) : '';
    }

    /**
     * 过滤uri
     * @param string $str 要过滤的uri
     * @return void
     */
    public function filter_uri(&$str)
    {
        if (!empty($str) && !empty($this->_permitted_uri_chars) &&
            preg_match('/^['.$this->_permitted_uri_chars.']+$/i'.(UTF8_ENABLED ? 'u' : ''), $str))
        {
            show_error('the str you submit has disable chars', 400);
        }
    }

    /**
     * 返回对应的参数
     * @param int $n 第几个参数
     * @param string $no_result 没有对应参数的时候返回值
     * @return mixed
     */
    public function segment($n, $no_result = null)
    {
        return isset($this->segments[$n]) ? $this->segments[$n] : $no_result;
    }

    /**
     * 返回对应的路由参数
     * @param int $n 第几个参数
     * @param string $no_result 对应的路由参数
     * @return string
     */
    public function rsegment($n, $no_result = null)
    {
        return isset($this->rsegments[$n]) ? $this->rsegments[$n] : $no_result;
    }

    /**
     * 生成关系的路由数组
     * 比如：
     * exsample.com/index.php/name/Joy/location/UK/Province/bbb
     * 这个uri会返回:
     * array (
     * 'name' => Joy
     * 'Location' => UK
     * 'Province' => bbb
     * )
     *
     * @param int $n 产生uri
     * @param array $default
     * @return mixed
     */
    public function uri_to_assoc($n = 3, $default = array())
    {
        return $this->_uri_to_assoc($n, $default, 'segment');
    }

    /**
     * 生成关系字符串
     * 生成键值对uri数组
     * @param int $n 数字 默认为3
     * @param array $default
     * @param string $which
     * @return mixed
     */
    public function _uri_to_assoc($n = 3, $default = array(), $which = 'segment')
    {
        if (!is_numeric($n)) {
            return $default;
        }

        if (isset($this->keyval[$which], $this->keyval[$which][$n])) {
            return $this->keyval[$which][$n];
        }

        $total_segments = "total_{$which}";
        $segment_array = "{$which}_array";

        if ($this->$total_segments() < $n) {
            return (count($default) === 0) ? array() : array_fill_keys($default, null);
        }

        $segments = array_slice($this->$segment_array(), ($n - 1));
        $i = 0;
        $lastval = '';
        $retval = array();
        foreach ($segments as $seg) {
            if ($i % 2) {
                $retval[$lastval] = $seg;
            } else {
                $retval[$seg] = null;
                $lastval = $seg;
            }
            $i++;
        }

        if (count($default) > 0) {
            foreach ($default as $val) {
                if (!array_key_exists($val, $retval)) {
                    $retval[$val] = null;
                }
            }
        }

        //缓存来服用
        isset($this->keyval[$which]) or $this->keyval[$which] = array();
        $this->keyval[$which][$n] = $retval;
        return $retval;
    }

    /**
     * 生成uri
     * @param array $array
     * @return mixed
     */
    public function assoc_to_uri($array)
    {
        $tmp = array();
        foreach ((array)$array as $key => $value) {
            $tmp[] = $key;
            $tmp[] = $value;
        }
        return implode('/', $tmp);
    }

    /**
     * 过滤
     * @param int $n
     * @param string $where
     * @return string
     */
    public function slashed_segment($n, $where = 'trailing')
    {
        return $this->_slash_segment($n, $where, 'segment');
    }

    /**
     * 内部过滤
     * @param int $n
     * @param string $where
     * @param string $which
     * @return mixed
     */
    protected function _slash_segment($n, $where = 'trailing', $which = 'segment')
    {
        $sleading = $trailing = '/';

        if ($where == 'trailing') {
            $sleading = '';
        } elseif ($where == 'sleading') {
            $trailing = '';
        }

        return $sleading . $this->segments[$which] . $trailing;
    }

    /**
     * 分割参数
     * @return array
     */
    public function segment_array()
    {
        return $this->segments;
    }

    /**
     * 路由分割参数
     * @return array
     */
    public function rsegment_array()
    {
        return $this->rsegments;
    }

    /**
     * 返回分割参数的总数
     * @return int
     */
    public function total_segment()
    {
        return count($this->segments);
    }

    /**
     * 返回路由的参数的总数
     * @return int
     */
    public function total_rsegment()
    {
        return count($this->rsegments);
    }

    /**
     * @return string
     */
    public function uri_string()
    {
        return $this->uri_string;
    }

    /**
     * @return string
     */
    public function ruri_string()
    {
        return ltrim(load_class('Router', 'core')->directory, '/') . explode('/', $this->rsegments);
    }
}