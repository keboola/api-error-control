<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\EventListener;

use Exception;
use Generator;
use Keboola\CommonExceptions\ExceptionWithContextInterface;
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
use Throwable;

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
            HttpKernelInterface::MAIN_REQUEST,
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
        self::assertIsArray($responseBody);
        self::assertCount(4, $responseBody);
        self::assertStringStartsWith('exception-', $responseBody['exceptionId']);
        self::assertEquals('12', $responseBody['code']);
        self::assertEquals('Internal Server Error occurred.', $responseBody['error']);
        self::assertEquals('error', $responseBody['status']);
        $record = $handler->getRecords()[0];
        self::assertEquals(Logger::CRITICAL, $record['level']);
        self::assertIsArray($record['context']);
        self::assertArrayHasKey('exception', $record['context']);
        self::assertEquals($exception, $record['context']['exception']);
        self::assertEquals([], $record['context']['context']);
        self::assertIsString($record['context']['exceptionId']);
        self::assertStringStartsWith('exception-', $record['context']['exceptionId']);
        self::assertSame($responseBody['exceptionId'], $record['context']['exceptionId']);
    }

    public function testHandleUserExceptionInterface(): void
    {
        $request = new Request();
        $exception = new class ('test user exception', 421) extends Exception implements UserExceptionInterface {
        };
        $event = new ExceptionEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
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
        self::assertIsArray($responseBody);
        self::assertCount(5, $responseBody);
        self::assertStringStartsWith('exception-', $responseBody['exceptionId']);
        self::assertEquals('421', $responseBody['code']);
        self::assertEquals('test user exception', $responseBody['error']);
        self::assertEquals('error', $responseBody['status']);
        self::assertEquals([], $responseBody['context']);
        $record = $handler->getRecords()[0];
        self::assertEquals(Logger::ERROR, $record['level']);
        self::assertIsArray($record['context']);
        self::assertEquals($exception, $record['context']['exception']);
        self::assertEquals([], $record['context']['context']);
        self::assertIsString($record['context']['exceptionId']);
        self::assertStringStartsWith('exception-', $record['context']['exceptionId']);
        self::assertSame($responseBody['exceptionId'], $record['context']['exceptionId']);
    }

    public function exceptionsWithContextProvider(): Generator
    {
        yield 'UserException' => [
            'exception' => new class ('exception', 421) extends Exception implements
                UserExceptionInterface,
                ExceptionWithContextInterface {
                public function getContext(): array
                {
                    return [
                        'validation_errors' => [
                            'Field is missing.',
                        ],
                    ];
                }
            },
            'statusCode' => 421,
            'exceptionCode' => '421',
            'message' => 'exception',
            'expectedBody' => [
                'error' => 'exception',
                'code' => 421,
                'status' => 'error',
                'context' => [
                    'validation_errors' => ['Field is missing.'],
                ],
            ],
        ];

        yield 'HttpException' => [
            'exception' => new class (421, 'exception') extends HttpException implements ExceptionWithContextInterface {
                public function getContext(): array
                {
                    return [
                        'validation_errors' => [
                            'Field is missing.',
                        ],
                    ];
                }
            },
            'statusCode' => 421,
            'exceptionCode' => '421',
            'message' => 'exception',
            'expectedBody' => [
                'error' => 'exception',
                'code' => 421,
                'status' => 'error',
                'context' => [
                    'validation_errors' => ['Field is missing.'],
                ],
            ],
        ];

        yield 'InternalException' => [
            'exception' => new class ('exception', 12) extends Exception implements ExceptionWithContextInterface {
                public function getContext(): array
                {
                    return [
                        'validation_errors' => [
                            'Field is missing.',
                        ],
                    ];
                }
            },
            'statusCode' => 500,
            'exceptionCode' => '12',
            'message' => 'Internal Server Error occurred.',
            'expectedBody' => [
                'error' => 'Internal Server Error occurred.',
                'code' => 12,
                'status' => 'error',
            ],
        ];
    }

    /**
     * @dataProvider exceptionsWithContextProvider
     */
    public function testHandleExceptionWithContext(
        Throwable $exception,
        int $statusCode,
        string $exceptionCode,
        string $message,
        array $expectedBody
    ): void {
        $request = new Request();
        $event = new ExceptionEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
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
        self::assertEquals($statusCode, $response->getStatusCode());

        $responseBody = json_decode((string) $response->getContent(), true);
        self::assertIsArray($responseBody);
        self::assertStringStartsWith('exception-', $responseBody['exceptionId']);
        unset($responseBody['exceptionId']);

        self::assertSame(
            $expectedBody,
            $responseBody
        );
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
            HttpKernelInterface::MAIN_REQUEST,
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
        self::assertIsArray($responseBody);
        self::assertCount(5, $responseBody);
        self::assertStringStartsWith('exception-', $responseBody['exceptionId']);
        self::assertEquals('0', $responseBody['code']);
        self::assertEquals('test user exception', $responseBody['error']);
        self::assertEquals('error', $responseBody['status']);
        self::assertEquals([], $responseBody['context']);
        $record = $handler->getRecords()[0];
        self::assertIsArray($record['context']);
        self::assertEquals(Logger::ERROR, $record['level']);
        self::assertEquals($exception, $record['context']['exception']);
        self::assertEquals([], $record['context']['context']);
        self::assertIsString($record['context']['exceptionId']);
        self::assertStringStartsWith('exception-', $record['context']['exceptionId']);
        self::assertSame($responseBody['exceptionId'], $record['context']['exceptionId']);
    }

    public function testHandleHttpException(): void
    {
        $request = new Request();
        $exception = new HttpException(403, 'test HTTP exception');
        $event = new ExceptionEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
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
        self::assertIsArray($responseBody);
        self::assertCount(5, $responseBody);
        self::assertStringStartsWith('exception-', $responseBody['exceptionId']);
        self::assertEquals('403', $responseBody['code']);
        self::assertEquals('test HTTP exception', $responseBody['error']);
        self::assertEquals('error', $responseBody['status']);
        self::assertEquals([], $responseBody['context']);
        $record = $handler->getRecords()[0];
        self::assertIsArray($record['context']);
        self::assertEquals(Logger::ERROR, $record['level']);
        self::assertEquals($exception, $record['context']['exception']);
        self::assertEquals([], $record['context']['context']);
        self::assertIsString($record['context']['exceptionId']);
        self::assertStringStartsWith('exception-', $record['context']['exceptionId']);
        self::assertSame($responseBody['exceptionId'], $record['context']['exceptionId']);
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
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $listener = new ExceptionListener($logger);
        $listener->onKernelException($event);
        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertEquals(1, preg_match(
            '#{"error":"test exception with special \\\" \' characters < > \^ \$ & end","code":0,' .
            '"exceptionId":"exception-[a-z0-9]+","status":"error","context":\[\]}#',
            (string) $response->getContent()
        ));
    }
}
