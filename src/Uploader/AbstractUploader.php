<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Uploader;

abstract class AbstractUploader
{
    protected const DEFAULT_URL_PREFIX = '/admin/utils/logs?file=';
    protected const DEFAULT_PATH = 'debug-files';

    /**
     * @var string
     */
    protected $urlPrefix;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $storageApiUrl;

    protected function generateFilename(
        string $contentType = 'text/html',
        ?string $originalFilePath = null
    ): string {
        if ($originalFilePath === null) {
            switch ($contentType) {
                case 'application/json':
                    $fileExtension = 'json';
                    break;
                default:
                    $fileExtension = 'html';
            }
            $fileName = uniqid() . '-log.' . $fileExtension;
        } else {
            $fileName = uniqid() . '-log.' . basename($originalFilePath);
        }
        return date('Y/m/d/H/') . date('Y-m-d-H-i-s') . '-' . $fileName;
    }

    protected function getUrl(string $logFileName): string
    {
        return $this->storageApiUrl . '/' . $this->urlPrefix . $logFileName;
    }

    public function __construct(
        string $storageApiUrl,
        string $path = self::DEFAULT_PATH,
        string $urlPrefix = self::DEFAULT_URL_PREFIX
    ) {
        $this->storageApiUrl = $storageApiUrl;
        $this->path = $path;
        $this->urlPrefix = $urlPrefix;
    }

    abstract public function upload(string $content, string $contentType = 'text/html'): string;

    abstract public function uploadFile(string $filePath, string $contentType = 'text/html'): string;
}
