<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Monolog;

use Keboola\ErrorControl\ExceptionIdGenerator;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

/**
 * @phpstan-type Level Logger::DEBUG|Logger::INFO|Logger::NOTICE|Logger::WARNING|Logger::ERROR|Logger::CRITICAL|Logger::ALERT|Logger::EMERGENCY
 * @phpstan-type LevelName 'DEBUG'|'INFO'|'NOTICE'|'WARNING'|'ERROR'|'CRITICAL'|'ALERT'|'EMERGENCY'
 * @phpstan-type Record array{
 *      message: string,
 *      context: mixed[],
 *      level: Level,
 *      level_name: LevelName,
 *      channel: string,
 *      datetime: \DateTimeImmutable,
 *      extra: mixed[],
 *      app?: string,
 *      pid?: string,
 *      priority?: string,
 * }
 */
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

    /**
     * @param Record|LogRecord $record
     * @return Record|LogRecord The processed record
     */
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

        // @phpstan-ignore-next-line
        return $record;
    }
}
