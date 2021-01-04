<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Monolog;

use Exception;
use Keboola\ErrorControl\Uploader\AbstractUploader;
use Keboola\ErrorControl\Uploader\UploaderFactory;
use Monolog\DateTimeImmutable;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Throwable;

class LogProcessor
{
    /**
     * @var AbstractUploader
     */
    private $uploader;

    /**
     * @var UploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var string
     */
    private $appName;

    /**
     * @var LogInfoInterface|null
     */
    private $logInfo;

    public function __construct(UploaderFactory $factory, string $appName)
    {
        $this->uploaderFactory = $factory;
        $this->appName = $appName;
    }

    private function addLogInfo(array $record): array
    {
        if ($this->logInfo) {
            return array_merge($this->logInfo->toArray(), $record);
        }
        return $record;
    }

    private function getUploader(): AbstractUploader
    {
        if (empty($this->uploader)) {
            $this->uploader = $this->uploaderFactory->getUploader();
        }
        return $this->uploader;
    }

    public function processRecord(array $record): array
    {
        $newRecord = [
            'message' => $record['message'],
            'level' => $record['level'],
            'level_name' => $record['level_name'],
            'context' => [],
            'channel' => $record['channel'] ?? '',
            'datetime' => $record['datetime'] ?? new DateTimeImmutable(true),
            'extra' => [],
            'app' => $this->appName,
            'pid' => getmypid(),
            'priority' => $record['level_name'],
        ];
        $newRecord = $this->addLogInfo($newRecord);
        if (!empty($record['context']['exceptionId'])) {
            /** @var Exception $exception */
            $exception = $record['context']['exception'];
            try {
                $renderer = new HtmlErrorRenderer(true);
                $newRecord['context']['attachment'] = $this->getUploader()->upload(
                    $renderer->render($exception)->getAsString()
                );
            } catch (Throwable $e) {
                $newRecord['context']['uploaderError'] = $e->getMessage();
            }
            $newRecord['context']['exceptionId'] = $record['context']['exceptionId'];
            $newRecord['context']['exception'] = [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTraceAsString(),
            ];
        }
        return $newRecord;
    }

    public function setLogInfo(LogInfoInterface $logInfo): void
    {
        $this->logInfo = $logInfo;
    }
}
