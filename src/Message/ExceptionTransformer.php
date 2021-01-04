<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Message;

use Keboola\CommonExceptions\ExceptionWithContextInterface;
use Keboola\CommonExceptions\UserExceptionInterface;
use Psr\Log\LoggerInterface;
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

    private static function getExceptionId(): string
    {
        return 'exception-' . md5(microtime());
    }

    private static function getExceptionContext(\Throwable $exception): array
    {
        if ($exception instanceof ExceptionWithContextInterface) {
            return $exception->getContext();
        }
        return [];
    }

    public static function transformException(LoggerInterface $logger, Throwable $exception): ExceptionMessage
    {
        $exceptionId = self::getExceptionId();
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $code = $statusCode;
            $logger->error($exception->getMessage(), [
                'exceptionId' => $exceptionId,
                'exception' => $exception,
                'context' => self::getExceptionContext($exception),
            ]);
        } elseif ($exception instanceof UserExceptionInterface) {
            $statusCode = $exception->getCode() ? $exception->getCode() : Response::HTTP_BAD_REQUEST;
            $code = $exception->getCode();
            $logger->error($exception->getMessage(), [
                'exceptionId' => $exceptionId,
                'exception' => $exception,
                'context' => self::getExceptionContext($exception),
            ]);
        } else {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $code = $exception->getCode();
            $logger->critical($exception->getMessage(), [
                'exceptionId' => $exceptionId,
                'exception' => $exception,
                'context' => self::getExceptionContext($exception),
            ]);
        }

        return new ExceptionMessage(
            self::getExceptionMessage($exception),
            $code,
            $exceptionId,
            $statusCode,
            self::getExceptionContext($exception)
        );
    }
}
