<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\EventListener;

use Exception;
use Keboola\CommonExceptions\UserExceptionInterface;
use Keboola\ErrorControl\EventListener\ExceptionListener;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ExceptionListenerTest extends TestCase
{
    public function setUp(): void
    {
        putenv('KERNEL_CLASS=wtf');
        parent::setUp();
    }

    private function getKernel(): HttpKernel
    {
        /** @var HttpKernel $kernel */
        $kernel = $this->getMockBuilder(HttpKernel::class)->disableOriginalConstructor()->getMock();
        return $kernel;
    }

    public function testHandleException(): void
    {
        $request = new Request();
        $exception = new Exception('test exception', 12);
        $event = new ExceptionEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $listener = new ExceptionListener($logger);
        $listener->onKernelException($event);
        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        self::assertTrue($response->headers->has('Access-Control-Allow-Headers'));
        self::assertEquals(500, $response->getStatusCode());
        $responseBody = json_decode((string) $response->getContent(), true);
        self::assertCount(4, $responseBody);
        self::assertStringStartsWith('exception-', $responseBody['exceptionId']);
        self::assertEquals('12', $responseBody['code']);
        self::assertEquals('Internal Server Error occurred.', $responseBody['error']);
        self::assertEquals('error', $responseBody['status']);
        $record = $handler->getRecords()[0];
        self::assertEquals(Logger::CRITICAL, $record['level']);
        self::assertEquals($exception, $record['context']['exception']);
        self::assertStringStartsWith('exception-', $record['context']['exceptionId']);
    }

    public function testHandleUserExceptionInterface(): void
    {
        $request = new Request();
        $exception = new class ('test user exception', 421) extends Exception implements UserExceptionInterface {
        };
        $event = new ExceptionEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $listener = new ExceptionListener($logger);
        $listener->onKernelException($event);
        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        self::assertTrue($response->headers->has('Access-Control-Allow-Headers'));
        self::assertEquals(421, $response->getStatusCode());
        $responseBody = json_decode((string) $response->getContent(), true);
        self::assertCount(4, $responseBody);
        self::assertStringStartsWith('exception-', $responseBody['exceptionId']);
        self::assertEquals('421', $responseBody['code']);
        self::assertEquals('test user exception', $responseBody['error']);
        self::assertEquals('error', $responseBody['status']);
        $record = $handler->getRecords()[0];
        self::assertEquals(Logger::ERROR, $record['level']);
        self::assertEquals($exception, $record['context']['exception']);
        self::assertStringStartsWith('exception-', $record['context']['exceptionId']);
    }

    public function testHandleUserExceptionZero(): void
    {
        $request = new Request();
        $exception = new class (
            'test user exception'
        ) extends RuntimeException implements UserExceptionInterface {
        };
        $event = new ExceptionEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $listener = new ExceptionListener($logger);
        $listener->onKernelException($event);
        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        self::assertTrue($response->headers->has('Access-Control-Allow-Headers'));
        self::assertEquals(400, $response->getStatusCode());
        $responseBody = json_decode((string) $response->getContent(), true);
        self::assertCount(4, $responseBody);
        self::assertStringStartsWith('exception-', $responseBody['exceptionId']);
        self::assertEquals('0', $responseBody['code']);
        self::assertEquals('test user exception', $responseBody['error']);
        self::assertEquals('error', $responseBody['status']);
        $record = $handler->getRecords()[0];
        self::assertEquals(Logger::ERROR, $record['level']);
        self::assertEquals($exception, $record['context']['exception']);
        self::assertStringStartsWith('exception-', $record['context']['exceptionId']);
    }

    public function testHandleHttpException(): void
    {
        $request = new Request();
        $exception = new HttpException(403, 'test HTTP exception');
        $event = new ExceptionEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $listener = new ExceptionListener($logger);
        $listener->onKernelException($event);
        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        self::assertTrue($response->headers->has('Access-Control-Allow-Headers'));
        self::assertEquals(403, $response->getStatusCode());
        $responseBody = json_decode((string) $response->getContent(), true);
        self::assertCount(4, $responseBody);
        self::assertStringStartsWith('exception-', $responseBody['exceptionId']);
        self::assertEquals('403', $responseBody['code']);
        self::assertEquals('test HTTP exception', $responseBody['error']);
        self::assertEquals('error', $responseBody['status']);
        $record = $handler->getRecords()[0];
        self::assertEquals(Logger::ERROR, $record['level']);
        self::assertEquals($exception, $record['context']['exception']);
        self::assertStringStartsWith('exception-', $record['context']['exceptionId']);
    }

    public function testExceptionEncoding(): void
    {
        $request = new Request();
        $exception = new class(
            'test exception with special " \' characters < > ^ $ & end'
        ) extends RuntimeException implements UserExceptionInterface {
        };
        $event = new ExceptionEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $listener = new ExceptionListener($logger);
        $listener->onKernelException($event);
        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertMatchesRegularExpression(
            '#{"error":"test exception with special \\\" \' characters < > \^ \$ & end","code":0,' .
                '"exceptionId":"exception-[a-z0-9]+","status":"error"}#',
            (string) $response->getContent()
        );
    }
}
