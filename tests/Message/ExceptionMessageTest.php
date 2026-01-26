<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Message;

use Exception;
use Keboola\ErrorControl\Message\ExceptionMessage;
use PHPUnit\Framework\TestCase;
use stdClass;

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

        $fullArray = $message->getFullArray();
        self::assertStringContainsString(
            'Keboola\ErrorControl\Tests\Message\ExceptionMessageTest->testAccessors()',
            $fullArray['trace'],
        );
        unset($fullArray['trace']);

        self::assertEquals(
            [
                'error' => 'foo',
                'code' => 123,
                'exception' => $ex,
                'exceptionId' => 'abc123',
                'status' => 'error',
                'context' => (object) ['foo' => 'bar'],
            ],
            $fullArray,
        );
        self::assertEquals(
            [
                'error' => 'foo',
                'code' => 123,
                'exceptionId' => 'abc123',
                'status' => 'error',
            ],
            $message->getSafeArray(),
        );
    }

    public function testGetFullArrayWithEmptyContext(): void
    {
        $ex = new Exception('test');
        $message = new ExceptionMessage('foo', 123, $ex, 'abc123', 300, []);
        $fullArray = $message->getFullArray();
        unset($fullArray['trace']);

        self::assertEquals(
            [
                'error' => 'foo',
                'code' => 123,
                'exception' => $ex,
                'exceptionId' => 'abc123',
                'status' => 'error',
                'context' => new stdClass(),
            ],
            $fullArray,
        );
    }

    public function testAccessorsWithStringCode(): void
    {
        $ex = new Exception('test');
        $message = new ExceptionMessage('foo', 'custom.string.code', $ex, 'abc123', 300, ['foo' => 'bar']);
        self::assertEquals('foo', $message->getError());
        self::assertEquals('custom.string.code', $message->getCode());
        self::assertEquals('test', $message->getException()->getMessage());
        self::assertEquals('abc123', $message->getExceptionId());
        self::assertEquals(300, $message->getStatusCode());
        self::assertEquals(['foo' => 'bar'], $message->getContext());

        $fullArray = $message->getFullArray();
        self::assertStringContainsString(
            'Keboola\ErrorControl\Tests\Message\ExceptionMessageTest->testAccessorsWithStringCode()',
            $fullArray['trace'],
        );
        unset($fullArray['trace']);

        self::assertEquals(
            [
                'error' => 'foo',
                'code' => 'custom.string.code',
                'exception' => $ex,
                'exceptionId' => 'abc123',
                'status' => 'error',
                'context' => (object) ['foo' => 'bar'],
            ],
            $fullArray,
        );
        self::assertEquals(
            [
                'error' => 'foo',
                'code' => 'custom.string.code',
                'exceptionId' => 'abc123',
                'status' => 'error',
            ],
            $message->getSafeArray(),
        );
    }
}
