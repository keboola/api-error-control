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
     * @var LogInfo|null
     */
    private $logInfo;

    public function __construct(S3Uploader $uploader, string $appName)
    {
        $this->uploader = $uploader;
        $this->appName = $appName;
    }

    private function addLoginfo(array $record): array
    {
        return array_merge($record, [
            'component' => $this->logInfo->getComponentId(),
            'runId' => $this->logInfo->getRunId(),
            'http' => [
                'url' => $this->logInfo->getUri(),
                'ip' => $this->logInfo->getClientIp(),
                'userAgent' => $this->logInfo->getUserAgent(),
            ],
            'token' => [
                'id' => $this->logInfo->getTokenId(),
                'description' => $this->logInfo->getTokenDescription(),
            ],
            'owner' => [
                'id' => $this->logInfo->getProjectId(),
                'name' => $this->logInfo->getProjectName(),
            ],
        ]);
    }

    private function addExceptionInfo(array $newRecord, string $exceptionId): array
    {
        $newRecord['context']['exceptionId'] = $exceptionId;
        /** @var \Exception $exception */
        $exception = $record['context']['exception'];
        $handler = new ExceptionHandler();
        try {
            $this->uploader->uploadToS3($handler->getHtml($exception));
        } catch (\Throwable $e) {
            $newRecord['context']['uploaderError'] = $e->getMessage();
        }
        $newRecord['context']['exception'] = [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
        ];
        return $newRecord;
    }

    public function processRecord(array $record): array
    {
        $newRecord = [
            'message' => $record['message'],
            'level' => $record['level'],
            'app' => $this->appName,
            'pid' => getmypid(),
            'priority' => $record['level_name'],
            'context' => [],
        ];
        if ($this->logInfo) {
            $newRecord = $this->addLoginfo($newRecord);
        }
        if (!empty($record['context']['exceptionId'])) {
            $newRecord = $this->addExceptionInfo($newRecord, $record['context']['exceptionId']);
        }
        return $newRecord;
    }

    public function setLogInfo(LogInfo $logInfo) : void
    {
        $this->logInfo = $logInfo;
    }
}
