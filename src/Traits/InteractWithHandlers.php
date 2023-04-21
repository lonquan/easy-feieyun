<?php

declare(strict_types=1);

namespace AntCool\Feieyun\Traits;

use AntCool\Feieyun\Exceptions\InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;

/**
 * @copyright Copy it from the EasyWechat
 * @see       https://github.com/w7corp/easywechat/blob/6.x/src/Kernel/Traits/InteractWithHandlers.php
 */
trait InteractWithHandlers
{
    /**
     * @var array<int, array{hash: string, handler: callable}>
     */
    protected array $handlers = [];

    /**
     * @return array<int, array{hash: string, handler: callable}>
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function with(callable|string $handler): static
    {
        return $this->withHandler($handler);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function withHandler(callable|string $handler): static
    {
        $this->handlers[] = $this->createHandlerItem($handler);

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    #[ArrayShape(['hash' => "string", 'handler' => "callable"])]
    public function createHandlerItem(callable|string $handler): array
    {
        return [
            'hash'    => $this->getHandlerHash($handler),
            'handler' => $this->makeClosure($handler),
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getHandlerHash(callable|string $handler): string
    {
        return match (true) {
            \is_string($handler) => $handler,
            \is_array($handler) => is_string($handler[0]) ? $handler[0] . '::' . $handler[1] : get_class(
                    $handler[0]
                ) . $handler[1],
            $handler instanceof \Closure => \spl_object_hash($handler),
            default => throw new InvalidArgumentException('Invalid handler: ' . \gettype($handler) . '.'),
        };
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function makeClosure(callable|string $handler): callable
    {
        if (is_callable($handler)) {
            return $handler;
        }

        if (class_exists($handler) && \method_exists($handler, '__invoke')) {
            /**
             * @psalm-suppress InvalidFunctionCall
             * @phpstan-ignore-next-line https://github.com/phpstan/phpstan/issues/5867
             */
            return fn(): mixed => (new $handler())(...\func_get_args());
        }

        throw new InvalidArgumentException(sprintf('Invalid handler: %s.', $handler));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function prepend(callable|string $handler): static
    {
        return $this->prependHandler($handler);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function prependHandler(callable|string $handler): static
    {
        \array_unshift($this->handlers, $this->createHandlerItem($handler));

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function without(callable|string $handler): static
    {
        return $this->withoutHandler($handler);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function withoutHandler(callable|string $handler): static
    {
        $index = $this->indexOf($handler);

        if ($index > -1) {
            unset($this->handlers[$index]);
        }

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function indexOf(callable|string $handler): int
    {
        foreach ($this->handlers as $index => $item) {
            if ($item['hash'] === $this->getHandlerHash($handler)) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function when(mixed $value, callable|string $handler): static
    {
        if (\is_callable($value)) {
            $value = \call_user_func($value, $this);
        }

        if ($value) {
            return $this->withHandler($handler);
        }

        return $this;
    }

    public function handle(mixed $result, mixed $payload = null): mixed
    {
        $next = $result = \is_callable($result) ? $result : fn(mixed $p): mixed => $result;

        foreach (\array_reverse($this->handlers) as $item) {
            $next = fn(mixed $p): mixed => $item['handler']($p, $next) ?? $result($p);
        }

        return $next($payload);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function has(callable|string $handler): bool
    {
        return $this->indexOf($handler) > -1;
    }
}
