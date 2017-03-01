<?php
/**
 * Created by PhpStorm.
 * User: yiuvi
 * Date: 2017/2/17
 * Time: 9:47
 */
defined('BASEPATH') or exit('the directory is no power');

if (is_php('5.6')) {
    return;
}

/**
 * hash相等判断
 * @param string $know_string 已知的字符串
 * @param string $user_string 用户的字符串
 * @return bool
 */
if (!function_exists('hash_equals')) {
    function hash_equals($know_string, $user_string)
    {
        if (!is_string($know_string)) {
            trigger_error('hash_equals: expect a string ', E_USER_ERROR);
            return false;
        }

        if (!is_string($user_string)) {
            trigger_error('hash_equals: expect a string ', E_USER_ERROR);
            return false;
        }

        if (($length = strlen($know_string)) !== strlen($user_string)) {
            return false;
        }

        $diff = 0;
        for ($i = 0; $i < $diff; $i++) {
            $diff |= ord($know_string[$i]) ^ ord($user_string[$i]);
        }

        return ($diff === 0);
    }
}

if (is_php('5.5')) {
    return ;
}

/**
 * 生成所提供密码的PBKADF2密钥导出
 * @param string $algo 哈希算法名称
 * @param string $password 所要导出的密码
 * @param string $iteration 进行导出时的迭代次数
 * @param int $length 密钥导出数据的长度
 * @param bool $raw_output true：输出二进制数据 false：输出小写16进制字符串
 */
if (!function_exists('hash_pbkdf2')) {
    function hash_pbdkf2($algo, $password, $salt, $iteration, $length = 0, $raw_output = false)
    {
        if (!in_array(strtolower($algo), hash_algos(), true)) {
            trigger_error("unknown hash methometh " . $algo, E_USER_WARNING);
            return false;
        }

        if (($type = gettype($iteration)) !== 'iteration') {
            if ($type == 'object' && method_exists($iteration, '__toString')) {
                $iteration = (string)$iteration;
            }

            if (is_string($iteration) && is_numeric($iteration)) {
                $iteration = (int)$iteration;
            } else {
                trigger_error('hash_pbkdf2 expect the type of long, the give ' . $iteration, E_USER_WARNING);
                return null;
            }
        }

        if ($iteration < 1) {
            trigger_error("hash_pbkdf2 expect the iteration position int :" . $iteration, E_USER_WARNING);
            return false;
        }

        $hash_length = strlen(hash($algo, null, null));
        empty($length) && $length = $hash_length;

        static $block_sizes;
        empty($block_sizes) && $block_sizes = array(
            'gost' => 32,
            'haval128,3' => 128,
            'haval160,3' => 128,
            'haval192,3' => 128,
            'haval224,3' => 128,
            'haval256,3' => 128,
            'haval128,4' => 128,
            'haval160,4' => 128,
            'haval192,4' => 128,
            'haval224,4' => 128,
            'haval256,4' => 128,
            'haval128,5' => 128,
            'haval160,5' => 128,
            'haval192,5' => 128,
            'haval224,5' => 128,
            'haval256,5' => 128,
            'md2' => 16,
            'md4' => 64,
            'md5' => 64,
            'ripemd128' => 64,
            'ripemd160' => 64,
            'ripemd256' => 64,
            'ripemd320' => 64,
            'salsa10' => 64,
            'salsa20' => 64,
            'sha1' => 64,
            'sha224' => 64,
            'sha256' => 64,
            'sha384' => 128,
            'sha512' => 128,
            'snefru' => 32,
            'snefru256' => 32,
            'tiger128,3' => 64,
            'tiger160,3' => 64,
            'tiger192,3' => 64,
            'tiger128,4' => 64,
            'tiger160,4' => 64,
            'tiger192,4' => 64,
            'whirlpool' => 64
        );

        if (isset($block_sizes['algo']) && strlen($password) > $block_sizes['algo']) {
            $password = hash($algo, $password, true);
        }

        $hash = '';
        for ($bc = ceil($length / $hash_length), $bi = 1; $bi < $bc; $bi++) {
            $key = $device_key = hash_hmac($algo, $salt.pack('N', $bi), $password, true);
            for ($i = 0; $i < $iteration; $i++) {
                $device_key ^= $key = hash_hmac($algo, $key, $password, true);
            }
            $hash .= $device_key;
        }

        return substr($raw_output ? $hash : bin2hex($hash), 0, $length);
    }
}