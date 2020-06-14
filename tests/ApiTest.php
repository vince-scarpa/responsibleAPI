<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use responsible\responsible;
use responsible\core\exception;

final class ApiTest extends TestCase
{
    private $options;

    public function setUp()
    {
        $apiOptions = new options;
        $this->options = $apiOptions->getApiOptions();
    }

    /**
     * @test Test if the Responsible API can initialise
     */
    public function testApiCanInitialise(): void
    {
        // $this->expectException(exception\errorException::class);

        // $responsible = responsible::API($this->options);

        // var_dump($responsible);
        // $responsible::response(true);
    }
}