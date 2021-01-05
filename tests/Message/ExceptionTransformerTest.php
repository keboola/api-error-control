<?php

namespace Keboola\ErrorControl\Tests\Message;

use Keboola\CommonExceptions\ExceptionWithContextInterface;
use Keboola\CommonExceptions\UserExceptionInterface;
use Keboola\ErrorControl\Message\ExceptionTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $ex = new \Exception('test');
        $message = ExceptionTransformer::transformException($ex)->getFullArray();
        self::assertStringContainsString('exception-', $message['exceptionId']);
        unset($message['exceptionId']);
        self::assertEquals(
            [
                'error' => 'Internal Server Error occurred.',
                'code' => 0,
                'exception' => $ex,
                'status' => 'error',
                'context' => [],
            ],
            $message
        );
    }

    public function testTransformUserException(): void
    {
        $ex = new class('test') extends \Exception implements UserExceptionInterface {
        };
        $message = ExceptionTransformer::transformException($ex)->getFullArray();
        self::assertStringContainsString('exception-', $message['exceptionId']);
        unset($message['exceptionId']);
        self::assertInstanceOf(UserExceptionInterface::class, $message['exception']);
        unset($message['exception']);
        self::assertEquals(
            [
                'error' => 'test',
                'code' => 0,
                'status' => 'error',
                'context' => [],
            ],
            $message
        );
    }

    public function testTransformHttpException(): void
    {
        $ex = new class('test') extends \Exception implements HttpExceptionInterface {
            public function getStatusCode(): int
            {
                return 123;
            }

            public function getHeaders(): array
            {
                return [];
            }
        };
        $message = ExceptionTransformer::transformException($ex)->getFullArray();
        self::assertStringContainsString('exception-', $message['exceptionId']);
        unset($message['exceptionId']);
        self::assertInstanceOf(HttpExceptionInterface::class, $message['exception']);
        unset($message['exception']);
        self::assertEquals(
            [
                'error' => 'Internal Server Error occurred.',
                'code' => 123,
                'status' => 'error',
                'context' => [],
            ],
            $message
        );
    }

    public function testTransformContextException(): void
    {
        $ex = new class('test') extends \Exception implements ExceptionWithContextInterface {
            public function getContext(): array
            {
                return ['bar' => 'Kochba'];
            }
        };
        $message = ExceptionTransformer::transformException($ex)->getFullArray();
        self::assertStringContainsString('exception-', $message['exceptionId']);
        unset($message['exceptionId']);
        self::assertInstanceOf(ExceptionWithContextInterface::class, $message['exception']);
        unset($message['exception']);
        self::assertEquals(
            [
                'error' => 'Internal Server Error occurred.',
                'code' => 0,
                'status' => 'error',
                'context' => ['bar' => 'Kochba'],
            ],
            $message
        );
    }
}
