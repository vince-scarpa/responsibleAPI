<?php

/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\adaptors
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */

namespace responsible\core\adaptors;

use responsible\core\interfaces\UserStateAdaptorInterface;

final class RedisUserStateAdaptor implements UserStateAdaptorInterface
{
    /**
     * Accepts either a native \Redis instance or a \Predis\Client.
     * Both expose the same setex / get / del surface used here.
     */
    public function __construct(
        private readonly \Redis|\Predis\Client $redis,
        private readonly string $keyPrefix = 'responsible:user:',
        private readonly int $defaultTtl = 300
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getUser(string|int $sub): ?object
    {
        $value = $this->redis->get($this->key($sub));

        // Native \Redis returns false on miss; Predis returns null
        if ($value === false || $value === null) {
            return null;
        }

        $decoded = json_decode($value);

        return ($decoded instanceof \stdClass) ? $decoded : null;
    }

    /**
     * {@inheritdoc}
     */
    public function setUser(string|int $sub, object $user, ?int $ttl = null): void
    {
        $this->redis->setex(
            $this->key($sub),
            $ttl ?? $this->defaultTtl,
            json_encode($user)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate(string|int $sub): void
    {
        $this->redis->del($this->key($sub));
    }

    private function key(string|int $sub): string
    {
        return $this->keyPrefix . $sub;
    }
}
