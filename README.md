常用 PHP 工具函数整理
---

### 1. 函数列表

- 已添加

| 函数名称 | 描述 |
| --- | --- |
| [sys_dump](https://github.com/whorusq/function.php#sys_dump) | 打印、调试变量 |
| sys_write_file | 向文件写入内容，通过 lock 防止多个进程同时操作 |
| [sys_download_file](https://github.com/whorusq/function.php#sys_download_file) | 文件直接下载，支持本地和远程 |
| sys_mkdir | 创建多级目录 |
| [sys_substr](https://github.com/whorusq/function.php#sys_substr) | 基于 UTF-8 的字符串截取 |
| sys_json_encode | 兼容性的 json_encode，不对汉字进行编码 |
| sys_client_ip | 获取客户端真实 IP |
| sys_ip_location | 根据 IP 获取对应的地理位置信息 |
| [sys_curl](https://github.com/whorusq/function.php#sys_curl) | 通用的 curl 封装 |
| sys_random_pwd | 生成随机密码串儿 |
| [sys_export_csv](https://github.com/whorusq/function.php#sys_export_csv) | 写入 CSV 文件并下载 |
| [sys_encrypt](https://github.com/whorusq/function.php#sys_encrypt--sys_decrypt) | 字符串加密 |
| [sys_decrypt](https://github.com/whorusq/function.php#sys_encrypt--sys_decrypt) | 字符串解密 |
| sys_uuid | 生成 uuid（简易版） |
| sys_dirs | 递归遍历指定目录的文件和子目录 |
| sys_password | 生成密码哈希值或检查密码是否与存储的 hash 值一致 |
| [sys_idcard](https://github.com/whorusq/function.php#sys_idcard) | 验证身份证号码 |

- 待添加

| 函数名称 | 描述 |
| --- | --- |
| sys_amount_in_words | 人民币金额大写 |
| sys_idcard | 验证身份证号，获取身份证信息 |
| sys_destroy | 自毁😆 |
| ... | 其它 |


### 2. 函数使用示例

#### sys_dump

```PHP
// string
$str = 'a string';
sys_dump($str);

// array
$arr = [
    'name'   => 'xiaoming',
    'age'    => 12,
    'scores' => [
        'math'    => 89,
        'en'      => 91,
        'chinese' => 99
    ]
];
// sys_dump($arr);
sys_dump($arr, true); // 打印完直接退出，不继续执行后面的代码

// object
$obj = new \Redis();
sys_dump($obj);
```

#### sys_download_file

```PHP
// 下载项目目录中的文件
sys_download_file('./tmp/demo.md', 'demo.md');

// 下载远程文件
sys_download_file('www.baidu.com/img/bd_logo1.png', '百度logo.png', true);
```

#### sys_substr

```PHP
$str = '这是一个待处理的字符串';

// 输出：这是一个待处理的
$str1 = sys_substr($str, 8);
sys_dump($str1);

// 输出：一个待处理的字符
$str2 = sys_substr($str, 8, 2);
sys_dump($str2);

// 输出：这是一个待...
$str3 = sys_substr($str, 5, 0, true);
sys_dump($str3);
```

#### sys_curl

```PHP
$params['method'] = 'GET';
$params['options'] = [
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded;charset=utf-8',
    ]
];
$result = sys_curl('这是请求地址', $params);
sys_dump($result);
```

#### sys_export_csv

```PHP
$data = [
    ['标题1', '标题2', '标题3'],
    ['Jerry', 12, '18812341234'],
    ['Tom', 18, '16612341234']
];
sys_export_csv($data, 'filename');
```

#### sys_encrypt / sys_decrypt

```PHP
$str = '一个待加密的字符串';
// 加密
$encryptedStr = sys_encrypt($str, 'sq', 120);
sys_dump($encryptedStr);

// 解密
sys_dump(sys_decrypt($encryptedStr, 'sq'));
```


#### sys_idcard

```PHP
$idInfo = sys_idcard('11112312312');
if ($idInfo !== false) {
    sys_dump($idInfo);
} else {
    sys_dump('无效的身份证号码!');
}
```