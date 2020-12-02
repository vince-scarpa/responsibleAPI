<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use responsible\core\exception;

final class errorTest extends TestCase
{
    private $options;

    public function setUp()
    {
        $apiOptions = new options;
        $this->options = $apiOptions->getApiOptions();
    }

    /**
     * Test exceptions
     */
    public function testErrors(): void
    {
        $apiOptions = new options;
        $MESSAGES = $apiOptions->getExceptionMessage();

        foreach ($MESSAGES as $type => $message) {
            
            $exceptionMessage = json_encode($apiOptions->getExceptionMessage($type), JSON_PRETTY_PRINT);
        
            $this->expectException(exception\responsibleException::class);
            $this->expectExceptionMessage($exceptionMessage);

            (new exception\errorException)
                ->setOptions($this->options)
                ->error($type);
        };
    }

    /**
     * Test exceptions
     */
    public function testError404(): void
    {   
        $exceptionMessage = json_decode('{
            "ERROR_CODE": 404,
            "ERROR_STATUS": "APPLICATION_ERROR",
            "MESSAGE": "`404_ERROR` is not defined as an error code"
        }');
        $exceptionMessage = json_encode($exceptionMessage, JSON_PRETTY_PRINT);

        $this->expectException(exception\responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        (new exception\errorException)
            ->setOptions($this->options)
            ->error('404_ERROR');
    }

    /**
     * Test exceptions
     */
    public function testError500(): void
    {   
        $exceptionMessage = json_decode('{
            "ERROR_CODE": 500,
            "ERROR_STATUS": "500_ERROR",
            "MESSAGE": "500 test message"
        }');
        $exceptionMessage = json_encode($exceptionMessage, JSON_PRETTY_PRINT);

        $this->expectException(exception\responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        (new exception\errorException)
            ->setOptions($this->options)
            ->message('500 test message')
            ->error('500_ERROR');
    }
}