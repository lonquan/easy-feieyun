<?php

declare(strict_types=1);

namespace AntCool\Feieyun\Traits;

use AntCool\Feieyun\Middleware\LoggerMiddleware;
use AntCool\Feieyun\Support\File;
use GuzzleHttp\Client as Http;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use AntCool\Feieyun\Exceptions\ResponseInvalidException;

trait InteractWithHttpClient
{
    protected Http $http;

    /**
     * @throws \Throwable
     */
    public function getJson(string $uri, array $query = []): array
    {
        return $this->request(method: 'GET', uri: $uri, options: ['query' => $query]);
    }

    /**
     * @throws \Throwable
     */
    public function postJson(string $uri, array $data = [], array $query = []): array
    {
        return $this->request(method: 'POST', uri: $uri, options: [
            'query'       => $query,
            'form_params' => $data,
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function deleteJson(string $uri, array $data = [], array $query = []): array
    {
        return $this->request(method: 'DELETE', uri: $uri, options: [
            'query'       => $query,
            'form_params' => $data,
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function uploadFile(string $uri, File $file, $name = 'file', array $data = [], array $query = []): array
    {
        return $this->request(method: 'POST', uri: $uri, options: [
            'query'     => $query,
            'multipart' => $this->buildForm($file, $name, $data),
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function request(string $method, string $uri, $options = []): array
    {
        $response = $this->http->request($method, $uri, $this->addSignToRequest($method, $options));
        $status = $response->getStatusCode();
        $body = $response->getBody();
        $body->rewind();
        $response = $body->getContents();

        if ($status < 200 || $status > 299) {
            throw new ResponseInvalidException($response, $status);
        }

        return json_decode($response, true);
    }

    protected function addSignToRequest(string $method, array $options): array
    {
        return $options;
    }

    protected function buildForm(File $file, string $name, array $data): array
    {
        $form = [];

        foreach ($data as $key => $value) {
            $form[] = ['name' => $key, 'contents' => $value];
        }

        $form[] = ['name' => $name, 'contents' => $file->getContents()];

        return $form;
    }

    protected function createHttp(array $config): self
    {
        if (empty($this->http)) {
            $this->http = new Http([
                'base_uri' => $config['base_uri'],
                'timeout'  => $config['timeout'] ?? 30,
                'verify'   => $config['verify'] ?? true,
                'handler'  => $this->withHandleStacks(),
                'headers'  => [
                    'User-Agent' => 'Easy-Feieyun',
                ],
            ]);
        }

        return $this;
    }

    protected function withHandleStacks(): HandlerStack
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $this->withLoggerMiddleware($stack);

        return $stack;
    }

    protected function withLoggerMiddleware(HandlerStack $stock): void
    {
        if ($this->config->get('debug', false)) {
            $stock->push(new LoggerMiddleware($this->config, $this->logger));
        }
    }
}
