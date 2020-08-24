<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\EventListener;

use Keboola\CommonExceptions\ExceptionWithContextInterface;
use Keboola\CommonExceptions\UserExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ExceptionListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    private function getHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => '*',
            'Access-Control-Allow-Headers' => '*',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Content-Type' => 'application/json',
        ];
    }

    private function getExceptionMessage(Throwable $exception): string
    {
        if (is_a($exception, UserExceptionInterface::class) || is_a($exception, HttpException::class)) {
            return  $exception->getMessage();
        }
        return 'Internal Server Error occurred.';
    }

    private function getExceptionId(): string
    {
        return 'exception-' . md5(microtime());
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $exceptionId = $this->getExceptionId();
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $code = $statusCode;
            $this->logger->error($exception->getMessage(), [
                'exceptionId' => $exceptionId,
                'exception' => $exception,
                'context' => $this->getExceptionContext($exception),
            ]);
        } elseif ($exception instanceof UserExceptionInterface) {
            $statusCode = $exception->getCode() ? $exception->getCode() : Response::HTTP_BAD_REQUEST;
            $code = $exception->getCode();
            $this->logger->error($exception->getMessage(), [
                'exceptionId' => $exceptionId,
                'exception' => $exception,
                'context' => $this->getExceptionContext($exception),
            ]);
        } else {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $code = $exception->getCode();
            $this->logger->critical(
                $exception->getMessage(),
                [
                    'exceptionId' => $exceptionId,
                    'exception' => $exception,
                    'context' => $this->getExceptionContext($exception),
                ]
            );
        }

        $message = [
            'error' => $this->getExceptionMessage($exception),
            'code' => $code,
            'exceptionId' => $exceptionId,
            'status' => 'error',
            'context' => $this->getExceptionContext($exception),
        ];
        $response = new JsonResponse($message, $statusCode, $this->getHeaders());
        $response->setEncodingOptions(0);
        $event->setResponse($response);
    }

    private function getExceptionContext(\Throwable $exception): ?array
    {
        if ($exception instanceof ExceptionWithContextInterface) {
            return $exception->getContext();
        }
        return null;
    }
}
