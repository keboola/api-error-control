<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Monolog;

use Exception;
use Keboola\ErrorControl\Uploader\AbstractUploader;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Throwable;

class LogProcessor
{
    /**
     * @var AbstractUploader
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

    public function __construct(AbstractUploader $uploader, string $appName)
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

    public function processRecord(array $record): array
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
            /** @var Exception $exception */
            $exception = $record['context']['exception'];
            try {
                $renderer = new HtmlErrorRenderer(true);
                $newRecord['context']['attachment'] = $this->uploader->upload(
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
