<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Message;

use Exception;
use Keboola\ErrorControl\Message\ExceptionMessage;
use PHPUnit\Framework\TestCase;

class ExceptionMessageTest extends TestCase
{
    public function testAccessors(): void
    {
        $ex = new Exception('test');
        $message = new ExceptionMessage(
            'foo',
            123,
            $ex,
            'abc123',
            300,
            [
                'foo' => 'bar',
                'errorCode' => 'validation.error',
            ]
        );
        self::assertEquals('foo', $message->getError());
        self::assertEquals(123, $message->getCode());
        self::assertEquals('test', $message->getException()->getMessage());
        self::assertEquals('abc123', $message->getExceptionId());
        self::assertEquals(300, $message->getStatusCode());
        self::assertEquals([
            'foo' => 'bar',
            'errorCode' => 'validation.error',
        ], $message->getContext());
        self::assertEquals(
            [
                'error' => 'foo',
                'code' => 123,
                'exception' => $ex,
                'exceptionId' => 'abc123',
                'status' => 'error',
                'context' => [
                    'foo' => 'bar',
                    'errorCode' => 'validation.error',
                ],
            ],
            $message->getFullArray()
        );
        self::assertEquals(
            [
                'message' => 'foo',
                'status' => 'error',
                'error' => 'validation.error',
            ],
            $message->getSafeArray()
        );

        // exception without string error code
        $message = new ExceptionMessage(
            'foo',
            123,
            $ex,
            'abc123',
            300,
            [
                'foo' => 'bar',
            ]
        );

        self::assertEquals(
            [
                'message' => 'foo',
                'status' => 'error',
            ],
            $message->getSafeArray()
        );
    }
}
