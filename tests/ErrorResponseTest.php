<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests;

use Exception;
use Keboola\ErrorControl\ErrorResponse;
use Keboola\ErrorControl\Message\ExceptionMessage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ErrorResponseTest extends TestCase
{
    public function testNewResponse(): void
    {
        $exceptionMessage = new ExceptionMessage(
            'error message',
            123,
            new Exception(),
            'exceptionId',
            456,
            []
        );

        $response = new ErrorResponse($exceptionMessage);

        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertSame('must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
        self::assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        self::assertSame('*', $response->headers->get('Access-Control-Allow-Methods'));
        self::assertSame('*', $response->headers->get('Access-Control-Allow-Headers'));
        self::assertSame(456, $response->getStatusCode());

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertSame([
            'message' => 'error message',
            'status' => 'error',
        ], $responseData);
    }

    public function testCreateFromException(): void
    {
        $exception = new RuntimeException('Error occurred', 123);

        $response = ErrorResponse::fromException($exception);

        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertSame('must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
        self::assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        self::assertSame('*', $response->headers->get('Access-Control-Allow-Methods'));
        self::assertSame('*', $response->headers->get('Access-Control-Allow-Headers'));
        self::assertSame(500, $response->getStatusCode());

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertSame([
            'message' => 'Internal Server Error occurred.',
            'status' => 'error',
        ], $responseData);
    }
}
