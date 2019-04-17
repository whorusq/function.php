<?php

//----------------------------------------------------------------------
// PHP 常用函数库
// Author： whoru.S.Q <whoru@sqiang.net>
// Link: http://sqiang.net
// Create: 2017-01-05 09:15:43
//----------------------------------------------------------------------

/**
 * 基本变量调整函数
 *
 * @param  mixed  $var    待打印输出的变量，支持字符串、数组、对象
 * @param  boolean $isExit 打印之后，是否终止程序继续运行
 * @return
 */
function sys_dump($var, $isExit = false)
{
    $preStyle = 'padding: 10px; background-color: #f2f2f2; border: 1px solid #ddd; border-radius: 5px;';
    if ($var && (is_array($var) || is_object($var))) {
        echo '<pre style="' . $preStyle . '">';
        if (is_array($var)) {
            print_r($var);
            echo '</pre>';
        } else if (is_object($var)) {
            echo (new \Reflectionclass($var));
            echo '</pre>';
        }
    } else {
        echo '<pre style="' . $preStyle . '">';
        var_dump($var);
        echo '</pre>';
    }
    if ($isExit) {
        exit();
    }
}

/**
 * 向文件写入内容，通过 lock 防止多个进程同时操作
 *
 * @param  string $file     文件完整地址（路径+文件名）
 * @param  string $contents 要写入的内容
 * @return 写入结果 true|false
 */
function sys_write_file($file, $contents)
{
    if (file_exists($file) && $contents != '') {
        $fp = fopen($file, 'w+');
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, $contents);
            flock($fp, LOCK_UN);
            return true;
        } else {
            return false;
        }
    } else {
        throw new \Exception('Invalid file or invalid written contents!');
    }
    return false;
}

/**
 * 通过浏览器直接下载文件
 *
 * @param  string  $path     文件地址：针对当前服务器环境的相对或绝对地址
 * @param  string  $name     下载后的文件名（包含扩展名）
 * @param  boolean $isRemote 是否是远程文件（通过 url 无法获取文件扩展名的必传参数 name）
 * @param  string  $proxy    代理，适用于需要使用代理才能访问外网资源的情况
 * @return 下载结果 true|false
 */
function sys_download_file($path, $name = null, $isRemote = false, $proxy = '')
{

    $fileRelativePath = $path;
    $savedFileName = $name;
    if (!$savedFileName) {
        $file = pathinfo($path);
        if (!empty($file['extension'])) {
            $savedFileName = $file['basename'];
        } else {
            $errMsg = 'Extension get failed, parameter \'name\' is required!';
            throw new \Exception($errMsg);
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
        throw new \Exception('Invalid file: ' . $fileRelativePath);
        return false;
    }
}

/**
 * 创建多级目录
 *
 * @param  string  $path 目录路径
 * @param  integer $mod  目录权限（windows忽略）
 * @return  创建结果 true|false
 */
function sys_mkdir($path, $mod = 0777)
{
    if (!is_dir($path)) {
        return (mkdir($path, $mod, true)) ? true : false;
    }
    return false;
}

/**
 * 基于 UTF-8 的字符串截取
 *
 * @param  string  $str          待截取的字符串
 * @param  integer  $length       截取长度
 * @param  interger $start         开始下标
 * @param  boolean $showEllipsis 是否显示省略号
 * @return 截取后的最终字符串
 */
function sys_substr($str, $length, $start = 0, $showEllipsis = false)
{
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
 *
 * @param  array $arr 待编码的信息
 * @return 编码后的 json 字符串
 */
function sys_json_encode($arr)
{
    if (version_compare(PHP_VERSION, '5.4', '>')) {
        return json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else {
        foreach ($arr as $key => $value) {
            $arr[$key] = urlencode($value);
        }
        return urldecode(json_encode($arr));
    }
}

/**
 * 生成 uuid（简易版）
 *
 * @param integer $type 取值 1，格式：XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
 * @return 一个 32 位的字符串 或 带格式的 36 位字符串
 */
function sys_uuid($type = null)
{
    $uuid = md5(uniqid(rand(), true));
    if ($type && $type == 1) {
        $str = strtoupper($uuid);
        $uuid = substr($str, 0, 8) . '-' .
                substr($str, 8, 4) . '-' .
                substr($str, 12, 4) . '-' .
                substr($str, 16, 4) . '-' .
                substr($str, 20);
    }
    return $uuid;
}

/**
 * 获取客户端 IP 地址
 * @return IP 地址
 */
function sys_client_ip()
{
    $ipAddress = '';
    if (getenv('HTTP_CLIENT_IP')) {
        $ipAddress = getenv('HTTP_CLIENT_IP');
    } else if(getenv('HTTP_X_FORWARDED_FOR')) {
        $ipAddress = getenv('HTTP_X_FORWARDED_FOR');
    } else if(getenv('HTTP_X_FORWARDED')) {
        $ipAddress = getenv('HTTP_X_FORWARDED');
    } else if(getenv('HTTP_FORWARDED_FOR')) {
        $ipAddress = getenv('HTTP_FORWARDED_FOR');
    } else if(getenv('HTTP_FORWARDED')) {
       $ipAddress = getenv('HTTP_FORWARDED');
    } else if(getenv('REMOTE_ADDR')) {
        $ipAddress = getenv('REMOTE_ADDR');
    } else {
        $ipAddress = 'unknown';
    }
    return $ipAddress;
}

/**
 * 根据 IP 获取对应的地理位置信息：国家、地区、isp
 *
 * @param  string $ip 待查询的 ip 地址
 * @return arr IP 对应的信息
 *      - country 国家
 *      - location 地区
 *      - isp 运营商
 *      - ip 对应的 IP
 */
function sys_ip_location($ip)
{
    $clientIpInfo = [];

    // 淘宝接口
    $apiTaobao = 'http://ip.taobao.com//service/getIpInfo.php?ip=';
    $result1 = json_decode(sys_curl($apiTaobao . $ip), true);
    if ($result1['data'] && !is_string($result1['data'])) {
        $clientIpInfo = [
            'country' => $result1['data']['country'],
            'location' => $result1['data']['region'] . $result1['data']['city'],
            'isp' => $result1['data']['isp'],
            'ip' => $result1['data']['ip']
        ];
    }

    // 新浪接口
    if (!$clientIpInfo) {
        $apiSina = 'http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=';
        $result2 = sys_curl($apiSina . $ip);
        $arr = explode('=', $result2);
        $result2 = json_decode(rtrim($arr[1], ';'), true);
        if ($result2['ret'] == 1) {
            $clientIpInfo = [
                'country' => $result2['country'],
                'location' => $result2['province'] . $result2['city'],
                'isp' => $result2['isp'],
                // 'ip' => $result2['ip']
            ];
        }
    }

    return $clientIpInfo ?: ['ip' => $ip, 'message' => 'unknown'];
}

/**
 * 通用 curl 请求函数
 *
 * @param  string $url     待请求的接口地址
 * @param  array  $params  请求参数
 *      - method 请求方式，默认 POST
 *      - data 请求接口时候，一同提交的参数
 *      - options 其它 curl 可选参数
 * @return mixde 请求结果
 */
function sys_curl($url, $params = [])
{
    // 默认配置参数
    $postData = $params['data'] ?: [];
    $options = $params['options'] ?: [];
    $config = array(
        CURLOPT_HEADER => 0,
        CURLOPT_URL => $url, // 请求地址
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_TIMEOUT => 10, // 请求超时时间
        CURLOPT_POST => 1, // 默认 POST 请求
        CURLOPT_POSTFIELDS => http_build_query($postData) // 请求参数
    );

    //
    if (strtoupper($params['method']) == 'GET') {
        unset($config[CURLOPT_POST]);
        unset($config[CURLOPT_POSTFIELDS]);
    }

    // 执行请求，返回结果
    $ch = curl_init();
    curl_setopt_array($ch, $config + $options);
    if (! $result = curl_exec($ch)) {
        @trigger_error(curl_error($ch));
    }
    curl_close($ch);
    return $result;
}

/**
 * 将数据写入 CSV 文件并直接通过浏览器下载
 *
 * @param  array $rows     要导出的数据，格式：
 *     [
 *          ['标题1', '标题2', '标题3'],
 *          ['Jerry', 12, '18812341234'],
 *          ['Tom', 18, '16612341234'],
 *          ...
 *      ]
 * @param  string $filename 指定 csv 文件名，不带扩展名
 * @return boolean
 */
function sys_export_csv($rows, $filename = null)
{
   if ((!empty($rows)) && is_array($rows)) {

        // 指定下载文件格式
        $name = ($filename) ? $filename . ".csv" : "export.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $name);

        // 写入文件
        $fp = fopen('php://output', 'w');
        fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF))); // add BOM to fix UTF-8 in Excel
        foreach ($rows as $row) {
            if (!is_array($row)) {
                $row = ['Invalid data, array is required.'];
            }
            fputcsv($fp, $row);
        }
        return true;
   }
   throw new \Exception('Invalid parameter type, array is required.');
   return false;
}

/**
 * 生成随机密码串
 *
 * @param  integer $length 密码位数，默认 8 位
 * @param  integer $type   密码类型
 *      - 0 默认，字母 + 数字；
 *      - 1 字母 + 数字 + 特殊符号
 * @return string 一个按规则生成的随机密码串
 */
function sys_random_pwd($length = 8, $type = 0)
{
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
 *
 * @param  string  $str    待加密的字符串
 * @param  string  $key     自定义密钥串
 * @param  integer $expiry  密文有效期，时间戳，单位：秒
 * @return 加密后的字符串
 */
function sys_encrypt($str, $key = '', $expiry = 0)
{
    return dz_authcode($str, 'ENCODE', $key, $expiry);
}

/**
 * 解密
 *
 * @param  string $str 待解密的密文字符串
 * @param  string $key 加密时使用的密钥串
 * @return 解密后的字符串
 */
function sys_decrypt($str, $key = '')
{
    return dz_authcode($str, 'DECODE', $key);
}

/**
 * Discuz! 加密/解密函数
 *
 * @param  string  $string    明文或密文
 * @param  string  $operation 操作类型：DECODE 解密，不传或其它任意字符表示加密
 * @param  string  $key       秘钥串
 * @param  integer $expiry    密文有效期，时间戳，单位：秒
 * @return 密文或明文
 */
function dz_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{

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

/**
 * 遍历目录
 * @param  string $dir  待遍历的目录地址
 * @param  array  &$res 目录下的文件或子目录，名称以数组键的形式
 * @return 目录树
 */
function sys_dirs($dir, &$res = [])
{
    $excludeList = ['.', '..', '.DS_Store', '.git', '.gitignore', '.svn'];
    if (file_exists($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if (in_array($file, $excludeList)) {
                    continue;
                }
                if (is_file(realpath($dir) . '/' .$file)) {
                    $res[$file] = null;
                } else {
                    $res[$file] = sys_dirs(realpath($file), $res);
                }
            }
            closedir($dh);
        }
    }
    ksort($res);
    return $res;
}

/**
 * 加密、验证用户密码
 *
 * 提示：生成的加密串，推荐数据库存储长度 256
 *
 * @param  string $input  用户输入的密码串
 * @param  string $hashed 经过加密的密码 hash 值
 * @param  string $salt 密钥串，用于低版本 PHP 加密、解密时，不传则使用默认值
 * @return boolean|tring
 *         - 只传 $input，返回字符串的 hash 值；失败，返回 false
 *         - 传 $input 和 $hashed，检查密码是否正确，返回 true 或 false
 */
function sys_password($input, $hashed = null, $salt = 'password')
{
    if (function_exists('password_hash')) {
        if (!$hashed) {
            return password_hash($input, PASSWORD_DEFAULT);
        } else {
            return password_verify($input, $hashed);
        }
    } else { // 低版本 hash 处理， PHP < 5.5
        if (!$hashed) {
            return sys_encrypt(crypt($input), $salt) ?: false;
        } else {
            $originStr = sys_decrypt($hashed, $salt);
            if (!function_exists('hash_equals')) { // 低版本 hash_equals 兼容
                $checkHash = function() use($input, $originStr) {
                    if (strlen($str1) != strlen($str2)) {
                        return false;
                    } else {
                        $res = $str1 ^ $str2;
                        $ret = 0;
                        for ($i = strlen($res) - 1; $i >= 0; $i--) {
                            $ret |= ord($res[$i]);
                        }
                        return !$ret;
                    }
                };
                return $checkHash();
            } else {
                return hash_equals($originStr, crypt($input, $originStr));
            }
        }
    }
}

/**
 * 身份证号码验证，支持 18 位、15 位
 *
 * @param  string $id 待验证的身份证号码
 * @return array|boolean
 *         - false 无效的身份证号码
 *         - [] 有效身份证号码所包含的信息
 *             - birthday 生日
 *             - area 所属省、直辖市、自治区
 *             - sex 性别
 */
function sys_idcard($id)
{
    $idcardInfo = [];

    // 验证长度
    $len = strlen($id);
    if ($len != 15 && $len != 18) {
        return false;
    }

    // 验证所属区域
    $allArea = array(
        11 => "北京",  12 => "天津", 13 => "河北",   14 => "山西", 15 => "内蒙古", 21 => "辽宁",
        22 => "吉林",  23 => "黑龙江", 31 => "上海",  32 => "江苏",  33 => "浙江", 34 => "安徽",
        35 => "福建",  36 => "江西", 37 => "山东", 41 => "河南", 42 => "湖北",  43 => "湖南",
        44 => "广东", 45 => "广西",  46 => "海南", 50 => "重庆", 51 => "四川", 52 => "贵州",
        53 => "云南", 54 => "西藏", 61 => "陕西", 62 => "甘肃", 63 => "青海", 64 => "宁夏",
        65 => "新疆", 71 => "台湾", 81 => "香港", 82 => "澳门", 91 => "国外"
    );
    $idcardInfo['province'] = $allArea[substr($id, 0, 2)];
    if (!$idcardInfo['province']) {
        return false;
    }

    // 验证出生日期
    $isValidBirthdayDate = function($birthday) {
        $birthday = strlen($birthday) == 6 ? intval('19' . $birthday) : intval($birthday);
        $year = substr($birthday, 0, 4);
        $currYear = intval(date('Y'));
        if ($year < ($currYear - 110) || $year > $currYear) {
            return false; // 出生年份大于今年或小于110岁，都是无效的
        }
        return $birthday == date('Ymd', strtotime($birthday)) ?: false;
    };
    $birthday = $len == 18 ? substr($id, 6, 8) : substr($id, 6, 6);
    if ($isValidBirthdayDate($birthday)) {
        $idcardInfo['birthday'] = date('Y年m月d日', strtotime($birthday));
    } else {
        return false;
    }

    // 根据身份证号长度采用不同规则进行校验
    if ($len == 18) {

        // 验证身份证校验码，根据国家标准GB 11643-1999
        // $factor 加权因子
        // $verifyCodeList 校验码对应值
        $isValidVirifyCode = function($id) {
            $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
            $verifyCodeList = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
            $id17 = substr($id, 0, 17);
            $sum = 0;
            $len = strlen($id17);
            for ($i = 0; $i < $len; $i++) {
                $sum += $id17[$i] * $factor[$i];
            }
            $mode = $sum % 11;
            return $verifyCodeList[$mode] == strtoupper(substr($id, 17, 1)) ?: false;
        };
        if (!$isValidVirifyCode($id)) {
            return false;
        }

    } else if ($len == 15) {

    }

    // 获取性别信息
    // 18 位身份证是滴 17 位；15 位身份证是滴 15 位
    $sexId = $len == 18 ? substr($id, 16, 1) : substr($id, 14, 1);
    $idcardInfo['sex'] = $sexId % 2 == 0 ? '女' : '男';

    // 合法身份证号码，返回身所包含的具体信息
    return $idcardInfo;
}

/**
 * 根据时区获取准确的时间
 * 说明：原 date 函数会根据当前时区变化；gmdate 永远把时区当作 UTC+0
 * @param string $format 时间格式
 * @param integer $timestamp 待处理的时间戳
 * @param integer $zone 时区，默认：东 8 区
 * @return string
 */
function sys_date($format, $timestamp = 0, $zone = 8)
{
    $timestamp = intval($timestamp) > 0 ? intval($timestamp) : time();
    return gmdate($format, $timestamp + $zone * 3600);
}

