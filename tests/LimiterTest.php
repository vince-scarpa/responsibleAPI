<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use responsible\responsible;
use responsible\core\throttle\limiter;
use responsible\core\exception;

final class LimiterTest extends TestCase
{
    private $options;
    private $limiterConstructor;
    private $limiterNoConstructor;

    public function setUp()
    {
        $apiOptions = new options;
        $this->options = $apiOptions->getApiOptions();

        $limit = 10;
        $rate = 'MINUTE';

        $this->limiterConstructor = new limiter($limit, $rate);
        $this->limiterNoConstructor = new limiter();
    }

    /**
     * Test if the Responsible API limiter denies access
     */
    public function testNoAccountDeny(): void
    {
        $limiter = $this->limiterConstructor;
        $limiter->setOptions($this->options);

        $this->expectException(\Exception::class);

        $limiter->throttleRequest();
    }

    /**
     * Test if the Responsible API limiter options no constructor
     */
    public function testCanSetNoConstructorOptions(): void
    {
        $limiter = $this->limiterNoConstructor;
        $limiter->setOptions($this->options);
        $limiter->setupOptions();

        $getOptionsSet = $limiter->getOptions();

        $expectedKeys = [
            'requestType',
            'rateLimit',
            'rateWindow',
            'unlimited',
            'leak',
            'leakRate',
        ];

        foreach ($expectedKeys as $i => $key) {
            $this->assertArrayHasKey($key, $getOptionsSet);
        }
    }

    /**
     * Test if the Responsible API limiter options with constructor
     */
    public function testCanSetConstructorOptions(): void
    {
        $limiter = $this->limiterConstructor;
        $limiter->setOptions($this->options);
        $limiter->setupOptions();

        $getOptionsSet = $limiter->getOptions();

        $expectedKeys = [
            'requestType',
            'rateLimit',
            'rateWindow',
            'unlimited',
            'leak',
            'leakRate',
        ];

        foreach ($expectedKeys as $i => $key) {
            $this->assertArrayHasKey($key, $getOptionsSet);
        }
    }

    /**
     * Test if the Responsible API limiter rate window 
     * resolves the value when wrong input is given
     */
    public function testWrongRateWindowInputResolves(): void
    {
        $limiter = $this->limiterConstructor;

        /**
         * Key = Expected value
         * Value = Actual value
         */
        $wrongPossibilities = [
            60 => [],
            60 => new \stdClass,
            60 => 'foo/bar',
            10 => -10,
            1 => 1.03,
            60 => 60.03,
            230 => 230,
        ];

        $possition = 0;

        foreach ($wrongPossibilities as $expectedValue => $possibleTest) {
            $this->options['rateWindow'] = $possibleTest;
            $limiter->setOptions($this->options);
            $limiter->setupOptions();
            $getOptionsSet = $limiter->getOptions();

            $this->assertEquals($expectedValue, $limiter->getTimeframe(), "Time frame did not resolve to {$expectedValue}. Failed at possition {$possition}");

            $possition++;
        }
    }

    /**
     * Test if the Responsible API limiter leak rate 
     * resolves the value when wrong input is given
     */
    public function testWrongLeakRateInputResolves(): void
    {
        $limiter = $this->limiterConstructor;

        /**
         * Key = Expected value
         * Value = Actual value
         */
        $wrongPossibilities = [
            'default' => [],
            'default' => new \stdClass,
            'default' => 'foo/bar',
            'default' => -10,
            'default' => 1.03
        ];

        $possition = 0;

        foreach ($wrongPossibilities as $expectedValue => $possibleTest) {
            $this->options['leakRate'] = $possibleTest;
            $limiter->setOptions($this->options);
            $limiter->setupOptions();
            $getOptionsSet = $limiter->getOptions();

            $this->assertEquals($expectedValue, $limiter->getLeakRate(), "Leak rate did not resolve to {$expectedValue}. Failed at possition {$possition}");

            $possition++;
        }
    }
}