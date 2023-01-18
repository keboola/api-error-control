<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Monolog;

use Keboola\ErrorControl\ExceptionIdGenerator;
use Monolog\DateTimeImmutable;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

class LogProcessor implements ProcessorInterface
{
    /** @var string */
    private $appName;

    /** @var LogInfoInterface|null */
    private $logInfo;

    public function __construct(string $appName)
    {
        $this->appName = $appName;
    }

    public function setLogInfo(LogInfoInterface $logInfo): void
    {
        $this->logInfo = $logInfo;
    }

    public function __invoke(array|LogRecord $record): array|LogRecord
    {
        if ($record instanceof LogRecord) {
            $record = $record->toArray();
        }
        if ($this->logInfo !== null) {
            $record = array_merge($this->logInfo->toArray(), $record);
        }

        $context = $record['context'] ?? [];
        $exception = $context['exception'] ?? null;

        if ($exception instanceof Throwable) {
            unset($context['exception']);

            $context['exceptionId'] = $context['exceptionId'] ?? ExceptionIdGenerator::generateId();
            $context['exception'] = [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        $record['context'] = $context;
        $record['app'] = $this->appName;
        $record['pid'] = getmypid();
        $record['priority'] = $record['level_name'];
        $record['extra'] = [];

        return $record;
    }
}
