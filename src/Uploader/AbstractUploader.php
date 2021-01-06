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
        string $contentType = 'text/html'
    ): string {
        switch ($contentType) {
            case 'application/json':
                $fileExtension = 'json';
                break;
            default:
                $fileExtension = 'html';
        }
        return date('Y/m/d/H/') . date('Y-m-d-H-i-s') . '-' . uniqid() . '-log.' . $fileExtension;
    }

    protected function getUrl(string $logFileName): string
    {
        return rtrim($this->storageApiUrl, '/') . $this->urlPrefix . $logFileName;
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
}
