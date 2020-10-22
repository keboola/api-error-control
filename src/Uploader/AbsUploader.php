<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Uploader;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;

class AbsUploader extends AbstractUploader
{
    /**
     * @var string
     */
    private $absConnectionString;

    /**
     * @var string
     */
    private $absContainer;

    public function __construct(
        string $storageApiUrl,
        string $absConnectionString,
        string $absContainer,
        string $path = self::DEFAULT_PATH,
        string $urlPrefix = self::DEFAULT_URL_PREFIX
    ) {
        parent::__construct($storageApiUrl, $path, $urlPrefix);
        $this->absConnectionString = $absConnectionString;
        $this->absContainer = $absContainer;
    }

    public function upload(string $content, string $contentType = 'text/html'): string
    {
        $options = new CreateBlockBlobOptions();
        $fileName = $this->generateFilename($contentType);
        $options->setContentDisposition(sprintf('attachment; filename=%s', $fileName));
        $options->setContentType($contentType);
        $blobClient = BlobRestProxy::createBlobService($this->absConnectionString);
        $blobClient->createBlockBlob(
            $this->absContainer,
            $fileName,
            $content,
            $options
        );
        return $this->getUrl($fileName);
    }
}
