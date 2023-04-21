<h1 align="center"> EasyFeieyun - Feieyun PHP SDK </h1>

<p align="center"> PHP SDK for 飞鹅云</p>
<p align="center">Overtrue! Respect!</p>

## 文档链接

- [飞鹅云开发文档](http://help.feieyun.com/document.php)

## 安装

```shell
$ composer require antcool/easy-feieyun -vvv
```

## 使用

### 配置项

```php
$config = [
    'default'  => 'default',
    'accounts' => [
        'default' => [
            'user'           => 'xxx@xxx.com',
            'key'            => '.....',
            'notify_url'     => '', // 打印状态回调地址
            'valid_duration' => 60 * 60 * 2, // 打印订单有效时长, 最大 24 小时
        ],
        
        // 可配置多个账号
        'test' => [
            'user'           => 'xxx@xxx.com',
            'key'            => '.....',
            // 其他参数非必须
        ],
    ],

    'public_key' => '', // 飞鹅云公钥文件路径或内容

    'debug'        => true, // 开启将会在 runtime_path 下生成请求日志文件
    'runtime_path' => __DIR__ . '/_runtime',

    'http' => [
        'base_uri' => 'http://api.feieyun.cn/Api/Open/', // 飞鹅云 API 请求 URL
        'timeout'  => 30,
        'verify'   => false,
    ],
];

```

### 创建实例

```php
$app = new \AntCool\Feieyun\Application($config);
// 使用其他账号配置
$app->getConfig()->withAccount('test');

$config = $app->getConfig();
$client = $app->getClient();
$server = $app->getServer();
```

### API 调用示范

```php
// 添加打印机
$client->addPrinter(['111111111#xxxxx#测试4G', '22222222#xxxxxx#测试WIFI',])

// 小票打印
$client->printOrder(sn: '', content: '', items: 1, notifyUrl: false, duration: false);

// 标签打印
$client->printLabelOrder(sn: '', content: '', img: '', items: 1, notifyUrl: false, duration: false)

// 批量移除打印机
$client->deletePrinter(sn: 'string|array');

// 编辑打印机信息
$client->editPrinter(sn: '', name: '', phoneNum: '');

// 清除打印机队列
$client->clearPrinterSqs(sn: '');

// 查询订单打印状态
$client->queryOrderStatus(orderId: '');

// 查询指定打印机某天的订单统计数
$client->queryOrderCountByDate(sn: '', date: 'yyyy-mm-dd');

// 获取某台打印机状态
$client->queryPrinterStatus(sn: '');
```

### 打印状态回调

```php
$server = $app->getServer();

// 通知内容处理逻辑, 可多次调用
$server->with(function (array $message) {
    // $message 为经过签名验证的通知内容
    
    // $message['orderId'] 订单ID，由接口Open_printMsg返回。
    // $message['status'] 订单状态 	1：打印成功
    // $message['stime'] 订单状态变更UNIX时间戳，10位，精确到秒。
    // $message['sign'] 数字签名
});

// Laravel 内可以直接 return $response
$response = $server->serve();

// 其他需要自己创建响应内容的, 可以参考下面代码
$statusCode = $response->getStatusCode();
foreach ($response->getHeaders() as $name => $values) {
    header(sprintf('%s: %s', $name, implode(', ', $values)), true, $statusCode);
}
echo $response->getBody();
```

## Contributing

You can contribute in one of three ways:

1. ...
2. ...

## License

MIT
