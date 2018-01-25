常用 PHP 工具函数整理
---

### 1. 函数列表

- 已添加

| 函数名称 | 描述 |
| --- | --- |
| sys_php_version_valid | 检查当前环境的 PHP 版本是否满足要求 |
| sys_dump | 打印、调试变量 |
| sys_write_file | 向文件写入内容，通过 lock 防止多个进程同时操作 |
| sys_download_file | 文件直接下载，支持本地和远程 |
| sys_mkdir | 创建多级目录 |
| sys_substr | 基于 UTF-8 的字符串截取 |
| sys_json_encode | 兼容性的 json_encode，不对汉字进行编码 |
| sys_client_ip | 获取客户端真实 IP |
| sys_ip_location | 根据 IP 获取对应的地理位置信息 |
| sys_curl | 通用的 curl 封装 |
| sys_random_pwd | 生成随机密码串儿 |
| sys_export_csv | 写入 CSV 文件并下载 |
| sys_encrypt | 字符串加密 |
| sys_decrypt | 字符串解密 |
| sys_uuid | 生成 uuid（简易版） |
| sys_dirs | 递归遍历指定目录的文件和子目录 |
| sys_pwd | 生成密码哈希值或检查密码是否与存储的 hash 值一致 |
| sys_idcard | 验证身份证号码 |

- 待添加

| 函数名称 | 描述 |
| --- | --- |
| sys_amount_in_words | 人民币金额大写 |
| sys_idcard | 验证身份证号，获取身份证信息 |
| sys_destroy | 自毁😆 |
| ... | 其它 |


### 2. 函数使用示例







