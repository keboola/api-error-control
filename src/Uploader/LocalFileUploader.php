<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Uploader;

use Symfony\Component\Filesystem\Filesystem;

class LocalFileUploader extends AbstractUploader
{
    /**
     * @var string
     */
    private $localPath;

    public function __construct(
        string $storageApiUrl,
        string $localPath,
        string $path = self::DEFAULT_PATH,
        string $urlPrefix = self::DEFAULT_URL_PREFIX
    ) {
        parent::__construct($storageApiUrl, $path, $urlPrefix);
        $this->localPath = $localPath;
    }

    public function upload(string $content, string $contentType = 'text/html'): string
    {
        $fileName = $this->localPath . '/' . $this->generateFilename($contentType);

        if ($contentType === 'application/json') {
            $content = (string) json_encode($content, JSON_PRETTY_PRINT);
        }

        (new Filesystem)->dumpFile($fileName, $content);

        return $fileName;
    }
}
