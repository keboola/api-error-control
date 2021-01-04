<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\EventListener;

use Keboola\ErrorControl\Message\ExceptionTransformer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

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
        $message = ExceptionTransformer::transformException($this->logger, $event->getThrowable());
        $response = new JsonResponse($message->getMessage(), $message->getStatusCode(), $this->getHeaders());
        $response->setEncodingOptions(0);
        $event->setResponse($response);
    }
}
