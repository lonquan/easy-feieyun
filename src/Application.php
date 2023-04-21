<?php

declare(strict_types=1);

namespace AntCool\Feieyun;

use AntCool\Feieyun\Kernel\Client;
use AntCool\Feieyun\Kernel\Config;
use AntCool\Feieyun\Kernel\Server;
use AntCool\Feieyun\Support\Logger;
use Throwable;

class Application
{
    protected Config $config;

    protected Client $client;

    protected Server $server;

    protected ?Logger $logger = null;

    /**
     * @throws Exceptions\InvalidArgumentException
     */
    public function __construct(array|Config $config)
    {
        $this->config = is_array($config) ? new Config($config) : $config;

        if ($this->config->get('debug', false)) {
            $this->logger = new Logger($this->config);
        }
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @throws Throwable
     */
    public function getClient(): Client
    {
        return $this->client ?? $this->client = new Client($this->config, $this->logger);
    }

    public function getServer(): Server
    {
        return $this->server ?? $this->server = new Server($this->config, $this->logger);
    }
}
