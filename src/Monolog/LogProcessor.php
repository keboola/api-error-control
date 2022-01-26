<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Monolog;

use ErrorException;
use Keboola\ErrorControl\ExceptionIdGenerator;
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

    public function setLogInfo(LogInfoInterface $logInfo): void
    {
        $this->logInfo = $logInfo;
    }

    public function processRecord(array $record): array
    {
        if ($this->logInfo !== null) {
            $record = array_merge($this->logInfo->toArray(), $record);
        }

        $context = $record['context'] ?? [];
        $exception = $context['exception'] ?? null;

        if ($exception instanceof Throwable) {
            unset($context['exception']);

            $ignoreException =
                $exception instanceof ErrorException &&
                in_array($exception->getSeverity(), [E_DEPRECATED, E_USER_DEPRECATED], true)
            ;

            if (!$ignoreException) {
                try {
                    $renderer = new HtmlErrorRenderer(true);
                    $renderedException = $renderer->render($exception)->getAsString();
                    $context['attachment'] = $this->getUploader()->upload($renderedException);
                } catch (Throwable $e) {
                    $context['uploaderError'] = $e->getMessage();
                }
            }

            $context['exceptionId'] = $context['exceptionId'] ?? ExceptionIdGenerator::generateId();
            $context['exception'] = [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        return array_merge($record, [
            'channel' => $record['channel'] ?? '',
            'datetime' => $record['datetime'] ?? new DateTimeImmutable(true),
            'context' => $context,
            'app' => $this->appName,
            'pid' => getmypid(),
            'priority' => $record['level_name'],
            'extra' => [],
        ]);
    }

    private function getUploader(): AbstractUploader
    {
        if (empty($this->uploader)) {
            $this->uploader = $this->uploaderFactory->getUploader();
        }
        return $this->uploader;
    }
}
