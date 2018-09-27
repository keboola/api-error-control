<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\EventListener;

use Keboola\ErrorControl\Exception\UserException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

    private function getHeaders() : array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => '*',
            'Access-Control-Allow-Headers' => '*',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Content-Type' => 'application/json',
        ];
    }

    private function getExceptionMessage(\Throwable $exception): string
    {
        if (is_a($exception, UserException::class) || is_a($exception, HttpException::class)) {
            return  $exception->getMessage();
        }
        return 'Internal Server Error occurred.';
    }

    private function getExceptionId(): string
    {
        return 'exception-' . md5(microtime());
    }

    public function onKernelException(GetResponseForExceptionEvent $event) : void
    {
        $exception = $event->getException();
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $code = $statusCode;
        } elseif (is_a($exception, UserException::class)) {
            $statusCode = $exception->getCode() ? $exception->getCode() : Response::HTTP_BAD_REQUEST;
            $code = $exception->getCode();
        } else {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $code = $exception->getCode();
        }
        $exceptionId = $this->getExceptionId();

        $message = [
            'error' => $this->getExceptionMessage($exception),
            'code' => $code,
            'exceptionId' => $exceptionId,
            'status' => 'error',
        ];
        $this->logger->critical($exception->getMessage(), ['exceptionId' => $exceptionId, 'exception' => $exception]);

        $response = new JsonResponse($message, $statusCode, $this->getHeaders());
        $response->setEncodingOptions(0);
        $event->setResponse($response);
    }
}
