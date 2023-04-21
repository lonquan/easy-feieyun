<?php

declare(strict_types=1);

namespace AntCool\Feieyun\Kernel;

use AntCool\Feieyun\Exceptions\OpensslVerifyException;
use AntCool\Feieyun\Exceptions\SignInvalidException;
use AntCool\Feieyun\Support\Logger;
use AntCool\Feieyun\Traits\InteractWithHandlers;
use AntCool\Feieyun\Traits\InteractWithServerRequest;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Response;

class Server
{
    use InteractWithServerRequest;
    use InteractWithHandlers;

    protected array $body;

    public function __construct(protected Config $config, protected ?Logger $logger)
    {
    }

    /**
     * @throws Exception
     */
    public function serve(): ResponseInterface
    {
        $this->validateSign();

        // Response code 200 and empty body.
        return $this->handle(new Response(status: 200, body: 'SUCCESS'), $this->body);
    }

    /**
     * @throws OpensslVerifyException
     * @throws SignInvalidException
     */
    protected function validateSign(): void
    {
        $this->body = $this->getRequest()->getParsedBody();

        $rawString = sprintf(
            'orderId=%s&status=%s&stime=%s',
            $this->body['orderId'],
            $this->body['status'],
            $this->body['stime'],
        );

        $publicKey = openssl_pkey_get_public($this->tryReadPublicKeyContent());

        match (openssl_verify(data: $rawString, signature: base64_decode($this->body['sign']), public_key: $publicKey, algorithm: 'SHA256')) {
            0 => throw new SignInvalidException(),
            -1 => throw new OpensslVerifyException(openssl_error_string()),
            default => null,
        };
    }

    protected function tryReadPublicKeyContent(): string
    {
        $content = $this->config->getPublicKey();

        return is_file($content) ? file_get_contents($content) : $content;
    }
}