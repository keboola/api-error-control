<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Uploader;

abstract class AbstractUploader
{
    /**
     * @var string
     */
    protected $urlPrefix = '/admin/utils/logs?file=';

    /**
     * @var string
     */
    protected $path = 'debug-files';

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
        return $this->storageApiUrl . '/' . $this->urlPrefix . $logFileName;
    }

    public function __construct(string $storageApiUrl)
    {
        $this->storageApiUrl = $storageApiUrl;
    }

    abstract public function upload(string $content, string $contentType = 'text/html'): string;
}
