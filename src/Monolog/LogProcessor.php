<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Monolog;

use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

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
        if ($this->logInfo !== null) {
            $record['extra'] = array_merge($this->logInfo->toArray(), $record['extra']);
        }

        $record['extra']['app'] = $this->appName;
        $record['extra']['pid'] = getmypid();
        $record['extra']['priority'] = $record['level_name'];

        return $record;
    }
}
