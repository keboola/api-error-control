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
        $message = new ExceptionMessage('foo', 123, $ex, 'abc123', 300, ['foo' => 'bar']);
        self::assertEquals('foo', $message->getError());
        self::assertEquals(123, $message->getCode());
        self::assertEquals('test', $message->getException()->getMessage());
        self::assertEquals('abc123', $message->getExceptionId());
        self::assertEquals(300, $message->getStatusCode());
        self::assertEquals(['foo' => 'bar'], $message->getContext());
        self::assertEquals(
            [
                'error' => 'foo',
                'code' => 123,
                'exception' => $ex,
                'exceptionId' => 'abc123',
                'status' => 'error',
                'context' => ['foo' => 'bar'],
            ],
            $message->getFullArray()
        );
        self::assertEquals(
            [
                'error' => 'foo',
                'code' => 123,
                'exceptionId' => 'abc123',
                'status' => 'error',
                'context' => ['foo' => 'bar'],
            ],
            $message->getSafeArray()
        );
    }
}
