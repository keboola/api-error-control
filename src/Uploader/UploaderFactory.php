<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Uploader;

class UploaderFactory
{
    /** @var string */
    private $storageApiUrl;

    /** @var ?string */
    private $s3Bucket;

    /** @var ?string */
    private $s3Region;

    /** @var ?string */
    private $absConnectionString;

    /** @var ?string */
    private $absContainer;

    /** @var ?string */
    private $path;

    public function __construct(
        string $storageApiUrl,
        ?string $s3Bucket = null,
        ?string $s3Region = null,
        ?string $absConnectionString = null,
        ?string $absContainer = null,
        ?string $path = null
    ) {
        $this->storageApiUrl = $storageApiUrl;
        $this->s3Bucket = $s3Bucket;
        $this->s3Region = $s3Region;
        $this->absConnectionString = $absConnectionString;
        $this->absContainer = $absContainer;
        $this->path = $path;
    }

    public function getUploader(): ?AbstractUploader
    {
        if (!empty($this->s3Bucket) && !empty($this->s3Region)) {
            return new S3Uploader($this->storageApiUrl, $this->s3Bucket, $this->s3Region);
        } elseif (!empty($this->absConnectionString) && !empty($this->absContainer)) {
            return new AbsUploader($this->storageApiUrl, $this->absConnectionString, $this->absContainer);
        } elseif (!empty($this->path)) {
            return new LocalFileUploader($this->storageApiUrl, $this->path);
        } else {
            return null;
        }
    }
}
