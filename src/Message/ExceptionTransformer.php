<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Message;

use Keboola\CommonExceptions\ExceptionWithContextInterface;
use Keboola\CommonExceptions\UserExceptionInterface;
use Keboola\ErrorControl\ExceptionIdGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ExceptionTransformer
{
    private static function getExceptionMessage(Throwable $exception): string
    {
        if (is_a($exception, UserExceptionInterface::class) || is_a($exception, HttpException::class)) {
            return  $exception->getMessage();
        }
        return 'Internal Server Error occurred.';
    }

    private static function getExceptionContext(\Throwable $exception): array
    {
        if ($exception instanceof ExceptionWithContextInterface) {
            return $exception->getContext();
        }
        return [];
    }

    public static function transformException(Throwable $exception): ExceptionMessage
    {
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $code = $statusCode;
        } elseif ($exception instanceof UserExceptionInterface) {
            $statusCode = $exception->getCode() ?: Response::HTTP_BAD_REQUEST;
            $code = $exception->getCode();
        } else {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $code = $exception->getCode();
        }

        return new ExceptionMessage(
            self::getExceptionMessage($exception),
            $code,
            $exception,
            ExceptionIdGenerator::generateId(),
            $statusCode,
            self::getExceptionContext($exception)
        );
    }
}
