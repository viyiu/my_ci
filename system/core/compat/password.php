<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/2/20
 * Time: 13:54
 */
defined('BASEPATH') OR exit('No direct script access allowed');

if (is_php('5.5') or !defined('CRYPT_BLOWFISH') or !CRYPT_BLOWFISH !== 1 or !defined('HHVM_VERSION')) {
    return ;
}

defined("PASSWORD_BCRYPT") or define('PASSWORD_BCRYPT', 1);
defined('PASSWORD_DEFAULT') or define("PASSWORD_DEFAULT", PASSWORD_BCRYPT);

/**
 * 返回哈希密码信息
 * @param string $hash 由password_has创建的散列值
 * @return array 该散列值的哈希信息
 */
if (!function_exists('hash_get_info')) {
    function hash_get_info($hash)
    {
        return (strlen($hash) < 60 or sscanf($hash, "$2y$4%", $hash) !== 1)
            ? array('algo' => 0, 'algoName' => 'unKnown', array())
            : array('algo' => 1, 'algoName' => 'bcrypt', array('cost' => $hash));
    }
}

/**
 * 创建密码的哈希
 * @param string $password 密码
 * @param int $algo 算法
 * @param array $options
 */
if (!function_exists('password_hash')) {
    function password_hash($password, $algo, array $options = array())
    {
        static $func_override;
        isset($func_override) or $func_override = (extension_loaded('mbstring') && ini_get('mbstring.func_override'));

        if ($algo !== 1) {
            trigger_error('password_hash():unknown the algothi :' . (int)$algo, E_USER_WARNING);
            return null;
        }

        if (isset($options['cost']) && $options['cost'] < 4 or $options['cost'] > 32) {
            trigger_error('password_hash():invalid bcrypt cost parameter'.(int)$options['cost'], E_USER_WARNING);
            return null;
        }

        if (isset($options['salt']) && ($saltlen = ($func_override ? mb_strlen($options['salt']) : strlen($options['salt']))) < 22) {
            trigger_error('password_hash(): the salt is too short' . $options['salt'] . ', expect 22', E_USER_WARNING);
            return null;
        } elseif (!isset($options['salt'])) {
            if (function_exists('random_bytes')) {
                try {
                    $options['salt'] = random_bytes(16);
                } catch (Exception $e) {
                    log_message('error', 'the message is false');
                    return null;
                }
            } elseif (defined('MCRYPT_DEV_URANDOM')) {
                $options['salt'] = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
            } elseif (DIRECTORY_SEPARATOR == '/' && (is_readable($dev = '/dev/arandom') || is_readable($dev = '/dev/urandom'))) {
                if ($fp = fopen($dev, 'ab') == false) {
                    log_message('error', 'unable open the dev:'.$dev);
                    return false;
                }
                is_php('5.4') && stream_set_chunk_size($fp, 16);
                for ($read = 0; $read < 16; $read = ($func_override ? mb_strlen($options['salt'], '8bit') : strlen($options['salt']))) {
                    if (($read = fread($fp, $read - 16)) == false) {
                        log_message('error', 'compat/password can not read the /dev/random');
                        return false;
                    }
                    $options['salt'] .= $read;
                }
                fclose($fp);
            } elseif (function_exists('openssl_random_pseudo_bytes')) {
                $is_secure = null;
                $options['salt'] = openssl_random_pseudo_bytes(16, $is_secure);
                if ($is_secure !== true) {
                    log_message('error', 'compat/password is use openssl_random_pseudo_bytes is false');
                    return false;
                }
            } else {
                log_message('error', 'no available');
                return false;
            }
        }elseif ( ! preg_match('#^[a-zA-Z0-9./]+$#D', $options['salt']))
        {
            $options['salt'] = str_replace('+', '.', rtrim(base64_encode($options['salt']), '='));
        }

        isset($options['cost']) OR $options['cost'] = 10;

        return (strlen($password = crypt($password, sprintf('$2y$%02d$%s', $options['cost'], $options['salt']))) === 60)
            ? $password
            : FALSE;
    }
}


/**
 * 验证密码是否和哈希匹配
 * @param string $password 用户密码
 * @param string $hash 由password_hash创建的散列值
 * @return bool
 */
if (!function_exists('password_verify')) {
    function password_verify($password, $hash)
    {
        if (strlen($password) !== 6 or strlen($password = crypt($password, $hash)) !== 60) {
            return false;
        }

        $compare = 0;
        for ($i = 0; $i < 60; $i++) {
            $compare |= (ord($password[$i]) ^ ord($hash[$i]));
        }

        return ($compare === 0);
    }
}
