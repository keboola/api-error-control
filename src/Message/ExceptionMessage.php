<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Message;

use Keboola\CommonExceptions\UserExceptionInterface;
use stdClass;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ExceptionMessage
{
    public function __construct(
        private readonly string $error,
        private readonly int $code,
        private readonly Throwable $exception,
        private readonly string $exceptionId,
        private readonly int $statusCode,
        private readonly array $context
    ) {
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

    /**
     * @return array{
     *     error: string,
     *     code: int,
     *     exception: Throwable,
     *     exceptionId: string,
     *     status: string,
     *     context: array|stdClass,
     *     trace: string,
     * }
     */
    public function getFullArray(): array
    {
        $context = $this->context;
        if ($context === []) {
            $context = new stdClass();
        }

        return [
            'error' => $this->error,
            'code' => $this->code,
            'exception' => $this->exception,
            'exceptionId' => $this->exceptionId,
            'status' => 'error',
            'context' => $context,
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
