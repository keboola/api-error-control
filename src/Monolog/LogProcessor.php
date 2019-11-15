<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Monolog;

use Symfony\Component\Debug\ExceptionHandler;

class LogProcessor
{
    /**
     * @var S3Uploader
     */
    private $uploader;

    /**
     * @var string
     */
    private $appName;

    /**
     * @var LogInfoInterface|null
     */
    private $logInfo;

    public function __construct(S3Uploader $uploader, string $appName)
    {
        $this->uploader = $uploader;
        $this->appName = $appName;
    }

    private function addLogInfo(array $record): array
    {
        if ($this->logInfo) {
            return array_merge($this->logInfo->toArray(), $record);
        }
        return $record;
    }

    public function processRecord(array $record) : array
    {
        $newRecord = [
            'message' => $record['message'],
            'level' => $record['level'],
            'app' => $this->appName,
            'pid' => getmypid(),
            'priority' => $record['level_name'],
            'context' => [],
            'extra' => [],
        ];
        $newRecord = $this->addLogInfo($newRecord);
        if (!empty($record['context']['exceptionId'])) {
            /** @var \Exception $exception */
            $exception = $record['context']['exception'];
            $handler = new ExceptionHandler();
            try {
                $newRecord['context']['attachment'] = $this->uploader->uploadToS3($handler->getHtml($exception));
            } catch (\Throwable $e) {
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

    public function setLogInfo(LogInfoInterface $logInfo) : void
    {
        $this->logInfo = $logInfo;
    }
}
