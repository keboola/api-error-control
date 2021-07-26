<?php

declare(strict_types=1);

namespace Keboola\ErrorControl;

use Keboola\ErrorControl\Message\ExceptionMessage;
use Keboola\ErrorControl\Message\ExceptionTransformer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class ErrorResponse extends JsonResponse
{
    private const HEADERS = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => '*',
        'Access-Control-Allow-Headers' => '*',
        'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        'Content-Type' => 'application/json',
    ];

    /**
     * @var int
     */
    protected $encodingOptions = 0;

    public function __construct(ExceptionMessage $message)
    {
        parent::__construct($message->getSafeArray(), $message->getStatusCode(), self::HEADERS);
    }

    public static function fromException(Throwable $error): self
    {
        $message = ExceptionTransformer::transformException($error);
        return new self($message);
    }
}
