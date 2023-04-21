<?php

declare(strict_types=1);

namespace AntCool\Feieyun\Kernel;

use AntCool\Feieyun\Exceptions\InvalidArgumentException;
use Illuminate\Support\Collection;

class Config extends Collection
{
    protected array $requiredKeys = [
        'default', 'accounts',
    ];

    protected array $accountRequiredKeys = [
        'user', 'key',
    ];

    /**
     * @param  array  $attributes
     * @throws InvalidArgumentException
     */
    public function __construct(array $attributes)
    {
        $this->checkMissingKeys($this->requiredKeys, $attributes);
        parent::__construct($attributes);
        $this->withAccount($this->get('default'));
    }

    /**
     * @param  string  $account
     * @return $this
     * @throws InvalidArgumentException
     */
    public function withAccount(string $account): static
    {
        $config = data_get($this, 'accounts.' . $account, []);

        $this->checkMissingKeys($this->accountRequiredKeys, $config);

        $this->put('current', $config);

        return $this;
    }

    public function getUser(): string
    {
        return data_get($this, 'current.user');
    }

    public function getKey(): string
    {
        return data_get($this, 'current.key');
    }

    public function getPublicKey(): string
    {
        return data_get($this, 'public_key');
    }

    public function getNotifyUrl(string $url = null): ?string
    {
        return $url ?: data_get($this, 'current.notify_url');
    }

    public function getValidDuration(int $duration = null): ?int
    {
        return $duration ?: data_get($this, 'current.valid_duration');
    }

    /**
     * @param  array  $keys
     * @param  array  $values
     * @return bool
     * @throws InvalidArgumentException
     */
    public function checkMissingKeys(array $keys, array $values): bool
    {
        if (empty($keys)) {
            return true;
        }

        $missingKeys = [];

        foreach ($keys as $key) {
            if (!isset($values[$key])) {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            throw new InvalidArgumentException(sprintf("\"%s\" cannot be empty.", join(',', $missingKeys)));
        }

        return true;
    }
}
