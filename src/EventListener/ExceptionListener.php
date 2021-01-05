<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\EventListener;

use Keboola\CommonExceptions\UserExceptionInterface;
use Keboola\ErrorControl\Message\ExceptionTransformer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
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

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $message = ExceptionTransformer::transformException($exception);
        if (($exception instanceof HttpExceptionInterface) || ($exception instanceof UserExceptionInterface)) {
            $this->logger->error($exception->getMessage(), $message->getFullArray());
        } else {
            $this->logger->critical($exception->getMessage(), $message->getFullArray());
        }
        $response = new JsonResponse($message->getSafeArray(), $message->getStatusCode(), $this->getHeaders());
        $response->setEncodingOptions(0);
        $event->setResponse($response);
    }
}
