<?php

namespace AntCool\Feieyun\Support;

use AntCool\Feieyun\Kernel\Config;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as MonologLogger;

class Logger
{
    protected MonologLogger $logger;

    public function __construct(protected Config $config)
    {
        $this->logger = new MonologLogger('Feieyun');
        $this->logger->pushHandler(
            new RotatingFileHandler($this->config->get('runtime_path', '/tmp/easy-feieyun') . '/logs/easy-feieyun.log', 30)
        );
    }

    public function __call(string $name, array $arguments)
    {
        call_user_func_array([$this->logger, $name], $arguments);
    }
}
