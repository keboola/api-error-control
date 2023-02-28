<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Message;

use Keboola\CommonExceptions\UserExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ExceptionMessage
{
    /** @var string */
    private $error;
    /** @var int */
    private $code;
    /** @var Throwable */
    private $exception;
    /** @var string */
    private $exceptionId;
    /** @var int */
    private $statusCode;
    /** @var array */
    private $context;

    public function __construct(
        string $error,
        int $code,
        Throwable $exception,
        string $exceptionId,
        int $statusCode,
        array $context
    ) {
        $this->error = $error;
        $this->code = $code;
        $this->exception = $exception;
        $this->exceptionId = $exceptionId;
        $this->statusCode = $statusCode;
        $this->context = $context;
    }

    public function getSafeArray(): array
    {
        $data = $this->getFullArray();
        unset($data['exception']);
        unset($data['trace']);
        if (!$this->exception instanceof UserExceptionInterface &&
            !$this->exception instanceof HttpExceptionInterface
        ) {
            unset($data['context']);
        }
        return $data;
    }

    public function getFullArray(): array
    {
        return [
            'error' => $this->error,
            'code' => $this->code,
            'exception' => $this->exception,
            'exceptionId' => $this->exceptionId,
            'status' => 'error',
            'context' => $this->context,
            'trace' => $this->exception->getTraceAsString(),
        ];
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }

    public function getExceptionId(): string
    {
        return $this->exceptionId;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
