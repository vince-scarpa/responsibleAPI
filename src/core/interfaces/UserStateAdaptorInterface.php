<?php

/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\interfaces
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */

namespace responsible\core\interfaces;

interface UserStateAdaptorInterface
{
    /**
     * Retrieve a cached user state object by sub claim.
     * Returns null on cache miss.
     */
    public function getUser(string|int $sub): ?object;

    /**
     * Store a user state object keyed by sub claim.
     * TTL in seconds; null falls back to the adaptor's default.
     */
    public function setUser(string|int $sub, object $user, ?int $ttl = null): void;

    /**
     * Remove the cached user state for the given sub claim.
     * Call this on account suspension, deletion, or password change.
     */
    public function invalidate(string|int $sub): void;
}
