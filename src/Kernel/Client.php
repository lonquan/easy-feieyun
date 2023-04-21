<?php

declare(strict_types=1);

namespace AntCool\Feieyun\Kernel;

use AntCool\Feieyun\Support\File;
use AntCool\Feieyun\Support\Logger;
use AntCool\Feieyun\Traits\InteractWithHttpClient;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Psr\Log\InvalidArgumentException;
use Throwable;

class Client
{
    use InteractWithHttpClient;

    /**
     * @throws Throwable
     */
    public function __construct(protected Config $config, protected ?Logger $logger)
    {
        $this->createHttp($this->config->get('http'));
    }

    public function withHandleStacks(): HandlerStack
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $this->withLoggerMiddleware($stack);

        return $stack;
    }

    /**
     * 批量添加打印机
     *
     * @param  array  $printers
     * @return array
     * @throws Throwable
     */
    public function addPrinter(array $printers): array
    {
        if (count($printers) > 100) {
            throw new InvalidArgumentException('每次最多添加 100 台');
        }

        return $this->postJson('', [
            'apiname'        => 'Open_printerAddlist',
            'printerContent' => join("\n", $printers),
        ]);
    }

    /**
     * 小票机打印订单
     *
     * @param  string  $sn  打印机编号
     * @param  string  $content  打印内容, 不超过 5000 字节
     * @param  int  $items  打印次数
     * @param  bool|string  $notifyUrl  订单状态回调通知地址, false 为不使用该参数, true 使用 config 中的配置, 可传入值覆盖
     * @param  bool|int  $duration  订单有效期时长, false 为不使用该参数, true 使用 config中的配置, 可传入值覆盖, 请求为 time() + duration
     * @return array
     * @throws Throwable
     */
    public function printOrder(string $sn, string $content, int $items = 1, bool|string $notifyUrl = true, bool|int $duration = true): array
    {
        $data = ['apiname' => 'Open_printMsg', 'sn' => $sn, 'content' => $content, 'times' => $items];

        return $this->postJson('', $this->printOrderParametersHandler($data, $notifyUrl, $duration));
    }

    /**
     * 打印标签订单
     *
     * @param  string  $sn
     * @param  string  $content
     * @param  string|File|null  $img  图片二进制数据
     * @param  int  $items
     * @param  bool|string  $notifyUrl
     * @param  bool|int  $duration
     * @return array
     * @throws Throwable
     */
    public function printLabelOrder(
        string $sn,
        string $content,
        string|File $img = null,
        int $items = 1,
        bool|string $notifyUrl = true,
        bool|int $duration = true
    ): array {
        $data = ['apiname' => 'Open_printLabelMsg', 'sn' => $sn, 'content' => $content, 'times' => $items];

        if (is_null($img)) {
            return $this->postJson('', $this->printOrderParametersHandler($data, $notifyUrl, $duration));
        }

        return $this->uploadFile(
            uri: '',
            file: is_string($img) ? new File($img) : $img,
            name: 'img',
            data: $this->printOrderParametersHandler($data, $notifyUrl, $duration)
        );
    }

    /**
     * 删除打印机
     *
     * @param  string|array  $sn
     * @return array
     * @throws Throwable
     */
    public function deletePrinter(string|array $sn): array
    {
        return $this->postJson('', [
            'apiname' => 'Open_printerDelList',
            'snlist'  => join('-', (array) $sn),
        ]);
    }

    /**
     * 修改打印机信息
     *
     * @param  string  $sn
     * @param  string  $name
     * @param  string|null  $phoneNum  流量卡名字, 设置错误的流量卡可能导致打印机无法连接
     * @return array
     * @throws Throwable
     */
    public function editPrinter(string $sn, string $name, string $phoneNum = null): array
    {
        return $this->postJson('', array_filter([
            'apiname'  => 'Open_printerEdit',
            'sn'       => $sn,
            'name'     => $name,
            'phonenum' => $phoneNum,
        ]));
    }

    /**
     * 清空打印机队列
     *
     * @param  string  $sn
     * @return array
     * @throws Throwable
     */
    public function clearPrinterSqs(string $sn): array
    {
        return $this->postJson('', ['apiname' => 'Open_delPrinterSqs', 'sn' => $sn]);
    }

    /**
     * 查询打印机订单状态
     *
     * @param  string  $orderId
     * @return array
     * @throws Throwable
     */
    public function queryOrderStatus(string $orderId): array
    {
        return $this->postJson('', ['apiname' => 'Open_queryOrderState', 'orderid' => $orderId]);
    }

    /**
     * 查询指定打印机某天的订单统计数
     *
     * @param  string  $sn
     * @param  string  $date
     * @return array
     * @throws Throwable
     */
    public function queryOrderCountByDate(string $sn, string $date): array
    {
        return $this->postJson('', ['apiname' => 'Open_queryOrderInfoByDate', 'sn' => $sn, 'date' => $date]);
    }

    /**
     * 查询打印机状态
     *
     * @throws Throwable
     */
    public function queryPrinterStatus(string $sn): array
    {
        return $this->postJson('', [
            'apiname' => 'Open_queryPrinterStatus',
            'sn'      => $sn,
        ]);
    }

    /**
     * 订单打印额外参数处理
     *
     * @param  array  $data
     * @param  bool|string  $notifyUrl
     * @param  bool|int  $duration
     * @return array
     */
    protected function printOrderParametersHandler(array $data, bool|string $notifyUrl, bool|int $duration): array
    {
        $url = match (true) {
            $notifyUrl === true => $this->config->getNotifyUrl(),
            is_string($notifyUrl) && !empty($notifyUrl) => $notifyUrl,
            default => false,
        };

        $url && $data['backurl'] = $url;

        $duration = match (true) {
            $duration === true => $this->config->getValidDuration(),
            is_int($duration) && $duration > 0 => $duration,
            default => false,
        };

        $duration && $data['expired'] = time() + $duration;

        return $data;
    }

    /**
     * 添加请求签名
     *
     * @param  string  $method
     * @param  array  $options
     * @return array
     */
    protected function addSignToRequest(string $method, array $options): array
    {
        $time = time();
        $signContent = [
            'user'  => $this->config->getUser(),
            'stime' => (string) $time,
            'sig'   => sha1(sprintf('%s%s%s', $this->config->getUser(), $this->config->getKey(), $time)),
        ];

        match (strtoupper($method)) {
            'GET' => $options['query'] = array_merge($options['query'] ?? [], $signContent),
            'POST' => match (true) {
                isset($options['form_params']) => $options['form_params'] = array_merge($options['form_params'], $signContent),
                isset($options['multipart']) => $options['multipart'] = $this->appendSignContentToForm($options['multipart'], $signContent),
            },
        };

        return $options;
    }

    /**
     * 上传文件签名字段处理
     *
     * @param  array  $form
     * @param  array  $sign
     * @return array
     */
    protected function appendSignContentToForm(array $form, array $sign): array
    {
        foreach ($sign as $key => $value) {
            $form[] = ["name" => $key, "contents" => $value];
        }

        return $form;
    }
}
