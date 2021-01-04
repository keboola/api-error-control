<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Message;

class ExceptionMessage
{
    /** @var int */
    private $statusCode;
    /** @var string */
    private $error;
    /** @var int */
    private $code;
    /** @var string */
    private $exceptionId;
    /** @var array */
    private $context;

    public function __construct(string $error, int $code, string $exceptionId, int $statusCode, array $context)
    {
        $this->statusCode = $statusCode;
        $this->error = $error;
        $this->code = $code;
        $this->exceptionId = $exceptionId;
        $this->context = $context;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getMessage(): array
    {
        return [
            'error' => $this->error,
            'code' => $this->code,
            'exceptionId' => $this->exceptionId,
            'status' => 'error',
            'context' => $this->context,
        ];
    }
}
