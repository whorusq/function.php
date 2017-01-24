<?php
////////////////////////////////////////////////////////////////////////////////////////////
/**
 * 常用函数库
 * @author: whoru.S.Q <whoru.sun@gmail.com>
 * @date: 2017-01-05 09:15:43
 */
////////////////////////////////////////////////////////////////////////////////////////////

/**
 * 调式函数
 * @param  mixed  $param 要打印输出的数据
 * @param  string  $type  类型：array | json
 * @param  boolean $zh    当类型是 json 时，是否不编码中文
 * @return
 */
function sys_dump($param, $type = null, $zh = false) {
    if ($type) {
        if ($type == 'array') { // 数组原样输出
            echo '<pre>';
            print_r($param);
            echo '</pre>';
        } elseif ($type == 'json') { // json 字符串
            sys_out_json($param, $zh);
        }
    } else {
        var_dump($param);
    }
}

/**
 * 向文件写入内容，通过 lock 防止多个进程同时操作
 * @param  string $file     文件地址
 * @param  string $contents 要写入的内容
 * @return 写入结果 true|false
 */
function sys_write_file($file, $contents) {
    if (file_exists($file) && $contents != '') {
        $fp = fopen($file, 'w+');
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, $contents);
            flock($fp, LOCK_UN);
            return true;
        } else {
            echo 'File is locking...';
        }
    } else {
        echo 'Invalid file or contents!';
    }
    return false;
}

/**
 * 文件直接下载
 * @uses
 *
 *      sys_download_file('web服务器中的文件地址', 'test.jpg');
 *      sys_download_file('远程文件地址', 'test.jpg', true);
 *
 * @param  string  $path     文件地址：针对当前服务器环境的相对或绝对地址
 * @param  string  $name     下载后的文件名（包含扩展名）
 * @param  boolean $isRemote 是否是远程文件（通过 url 无法获取文件扩展名的必传参数 name）
 * @param  string  $proxy    代理，适用于需要使用代理才能访问外网资源的情况
 * @return 下载结果 true|false
 */
function sys_download_file($path, $name = null, $isRemote = false, $proxy = '') {

    $fileRelativePath = $path;
    $savedFileName = $name;
    if (!$savedFileName) {
        $file = pathinfo($path);
        if (!empty($file['extension'])) {
            $savedFileName = $file['basename'];
        } else {
            echo 'Extension get failed, parameter \'name\' is required!';
            return false;
        }
    }

    // 如果是远程文件，先下载到本地
    if ($isRemote) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $path);
        if ($proxy != '') {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
        $fileContent = curl_exec($ch);
        curl_close($ch);

        // 写入临时文件
        $fileRelativePath = tempnam(sys_get_temp_dir(), 'DL');
        $fp = @fopen($fileRelativePath, 'w+');
        fwrite($fp, $fileContent);
    }

    // 执行下载
    if (is_file($fileRelativePath)) {
        header('Content-Description: File Transfer');
        header('Content-type: application/octet-stream');
        header('Content-Length:' . filesize($fileRelativePath));
        if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) { // for IE
            header('Content-Disposition: attachment; filename="' . rawurlencode($savedFileName) . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $savedFileName . '"');
        }
        readfile($fileRelativePath);
        if ($isRemote) {
            unlink($fileRelativePath); // 删除下载远程文件时对应的临时文件
        }
        return true;
    } else {
        echo 'Invalid file: ' . $fileRelativePath;
        return false;
    }
}

/**
 * 创建多级目录
 * @param  string  $path 目录路径
 * @param  integer $mod  目录权限（windows忽略）
 * @return  创建结果 true|false
 */
function sys_mkdir($path, $mod = 0777) {
    if (!is_dir($path)) {
        return (mkdir($path, $mod, true)) ? true : false;
    } else {
        echo 'An existing directory: ' . $path;
    }
    return false;
}

/**
 * 基于 UTF-8 的字符串截取
 * @param  string  $str          待截取的字符串
 * @param  integer  $start        开始下标
 * @param  integer  $length       截取长度
 * @param  boolean $showEllipsis 是否显示省略号
 * @return 截取后的最终字符串
 */
function sys_substr_utf8($str, $start, $length = null, $showEllipsis = false) {
    $length = ($length) ? $length : 99999;
    $strFullLength = 0; // 字符串完整长度
    $finalStr = '';
    if (function_exists('mb_substr') && function_exists('mb_strlen')) {
        $strFullLength = mb_strlen($str, 'utf8');
        $finalStr = mb_substr($str, $start, min($length, $strFullLength), 'utf8');
    } else {
        // header('Content-Type:text/html;charset=utf8');
        $arr = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        $strFullLength = count($arr);
        $finalStr = join('', array_slice($arr, $start, min($length, $strFullLength)));
    }
    if ($showEllipsis && $length < $strFullLength) {
        $finalStr .= '...';
    }
    return $finalStr;
}

/**
 * 兼容性的 json_encode，不编码汉字
 * @param  array $arr 待编码的信息
 * @return
 */
function sys_json_encode($arr) {
    if (PHP_VERSION >= 5.4) {
        return json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else {
        // $encodedStr = urldecode(json_encode($this->_url_encode($str)));
        foreach ($arr as $key => $value) {
            $arr[$key] = urlencode($value);
        }
        return urldecode(json_encode($arr));
    }
}

/**
 * 输出数据 json 编码后的字符串
 * @param  array  $arr  要编码并输出的数据，一般是数组
 * @param  boolean $zhNo 是否编码中文
 * @return
 */
function sys_out_json($arr, $zhNo = false) {
    if ($zhNo) {
        echo sys_json_encode($arr);
    } else {
        echo json_encode($arr);
    }
    exit;
}

/**
 * 生成唯一 ID（简易版）
 * @return
 */
function sys_uuid($type = null) {
    $uuid = md5(uniqid(rand(), true));
    if ($type && $type == 1) { // 格式：XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
        $str = strtoupper($uuid);
        $uuid = substr($str, 0, 8) . '-' .
                substr($str, 8, 4) . '-' .
                substr($str, 12, 4) . '-' .
                substr($str, 16, 4) . '-' .
                substr($str, 20);
    }
    return $uuid;
}

// 获取客户端 IP 地址
function sys_client_ip() {

}

// 获取 IP 具体位置
function sys_ip_location($ip) {

}

// curl 请求接口
function sys_curl($url, $data = null, $method = 'POST') {

}

/**
 * 将数据导出到 CSV 文件
 * @param  array $rows     要导出的数据
 * @param  string $filename 指定的 csv 文件名
 * @return
 */
function sys_export_csv($rows, $filename = null) {
   if ((!empty($rows))) {

      $name = ($filename) ? $filename . ".csv" : "export.csv";
      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename=' . $name);

      # Start the ouput
      $fp = fopen('php://output', 'w');
      fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF))); // add BOM to fix UTF-8 in Excel
      foreach ($rows as $row) {
         fputcsv($fp, $row);
      }
      return true;
      // exit();
   }
   return false;
}

/**
 * 生成随机密码串儿
 * @param  integer $length 密码位数，默认 8 位
 * @param  integer $type   密码类型：0 默认，字母+数字；1 字母+数字+特殊符号
 * @return
 */
function sys_random_pwd($length = 8, $type = 0) {
    $number = range('0', '9');
    $words = array();
    foreach (range('A', 'Z') as $v) {
        if ($v == 'O' || $v == 'I' || $v == 'L') {
            continue;
        }
        $words[] = $v;
        $words[] = strtolower($v);
    }
    $teshu = array();
    if ($type == 1) {
        $teshu = array('!', '@', '#', '$', '%', '^', '*', '+', '=', '-', '&');
    }
    $arr = array_merge($number, $words, $teshu);
    shuffle($arr);
    return substr(str_shuffle(implode('', $arr)), 0, $length);
}

/**
 * 加密
 * @param  string  $str    待加密的明文串
 * @param  string  $key     密钥串
 * @param  integer $expiry  密文有效期，时间戳，单位：秒
 * @return
 */
function sys_encrypt($str, $key = '', $expiry = 0) {
    return dz_authcode($str, 'ENCODE', $key, $expiry);
}

/**
 * 解密
 * @param  string $str 密文串儿
 * @return
 */
function sys_decrypt($str) {
    return dz_authcode($str, 'DECODE');
}

/**
 * Discuz! 加密/解密函数
 * @param  string  $string    明文或密文
 * @param  string  $operation 操作类型：DECODE 解密，不传或其它任意字符表示加密
 * @param  string  $key
 * @param  integer $expiry    密文有效期，时间戳，单位：秒
 * @return 密文或明文
 */
function dz_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {

    $ckey_length = 4; // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    // $key = md5($key ? $key : C('AUTH_CODE_KEY')); // 密匙
    $keya = md5(substr($key, 0, 16)); // 密匙a会参与加解密
    $keyb = md5(substr($key, 16, 16)); // 密匙b会用来做数据完整性验证
    $keyc = $ckey_length ? (
        $operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)
    ) : ''; // 密匙c用于变化生成的密文
    $cryptkey = $keya.md5($keya.$keyc); // 参与运算的密匙
    $key_length = strlen($cryptkey);

    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，
    // 解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) :
        sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();

    // 产生密匙簿
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    // 核心加解密部分
    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ($operation == 'DECODE') {
        // 验证数据有效性，请看未加密明文的格式
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) &&
            substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        return $keyc.str_replace('=', '', base64_encode($result));
    }
}
