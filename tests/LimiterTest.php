<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use responsible\responsible;
use responsible\core\throttle\limiter;
use responsible\core\exception;

final class LimiterTest extends TestCase
{
    private $options;

    public function setUp()
    {
        $apiOptions = new options;
        $this->options = $apiOptions->getApiOptions();
    }

    /**
     * Test if the Responsible API limiter denies access
     */
    public function testNoAccountLimits(): void
    {
        $limit = 10;
        $rate = 'MINUTE';

        $limiter = new limiter($limit, $rate);
        $limiter->setOptions($this->options);

        $this->expectException(\Exception::class);

        $limiter->throttleRequest();
    }
}