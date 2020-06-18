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

        $this->assertContains('requestType', $getOptionsSet);
        $this->assertContains('rateLimit', $getOptionsSet);
        $this->assertContains('rateWindow', $getOptionsSet);
        $this->assertContains('unlimited', $getOptionsSet);
        $this->assertContains('leak', $getOptionsSet);
        $this->assertContains('leakRate', $getOptionsSet);
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

        $this->assertContains('requestType', $getOptionsSet);
        $this->assertContains('rateLimit', $getOptionsSet);
        $this->assertContains('rateWindow', $getOptionsSet);
        $this->assertContains('unlimited', $getOptionsSet);
        $this->assertContains('leak', $getOptionsSet);
        $this->assertContains('leakRate', $getOptionsSet);
    }

    /**
     * Test if the Responsible API limiter rate window 
     * resolves the value when wrong input is given
     */
    public function testWrongRateWindowInput(): void
    {
        $limiter = $this->limiterConstructor;
        
        $wrongPossibilities = [
            60 => [],
            60 => new \stdClass,
            60 => 'foo/bar',
            10 => -10
        ];

        $possition = 0;

        foreach ($wrongPossibilities as $resolveTo => $possibleTest) {
            $this->options['rateWindow'] = $possibleTest;
            $limiter->setOptions($this->options);
            $limiter->setupOptions();
            $getOptionsSet = $limiter->getOptions();

            $this->assertEquals($resolveTo, $limiter->getTimeframe(), "Time frame not reset to {$resolveTo} value. Failed at possition {$possition}");

            $possition++;
        }
    }
}