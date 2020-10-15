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

    public function __construct(string $storageApiUrl, string $absConnectionString, string $absContainer)
    {
        parent::__construct($storageApiUrl);
        $this->absConnectionString = $absConnectionString;
        $this->absContainer = $absContainer;
    }

    public function upload(string $content, string $contentType = 'text/html'): string
    {
        $options = new CreateBlockBlobOptions();
        $fileName = $this->generateFilename();
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
