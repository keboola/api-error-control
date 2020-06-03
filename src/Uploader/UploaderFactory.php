<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Uploader;

use RuntimeException;

class UploaderFactory
{
    /**
     * @var string
     */
    private $storageApiUrl;

    /**
     * @var ?string
     */
    private $s3bucket;

    /**
     * @var ?string
     */
    private $awsRegion;

    /**
     * @var ?string
     */
    private $absConnectionString;

    /**
     * @var ?string
     */
    private $absContainer;

    public function __construct(
        string $storageApiUrl,
        ?string $s3bucket = null,
        ?string $awsRegion = null,
        ?string $absConnectionString = null,
        ?string $absContainer = null
    ) {
        $this->storageApiUrl = $storageApiUrl;
        $this->s3bucket = $s3bucket;
        $this->awsRegion = $awsRegion;
        $this->absConnectionString = $absConnectionString;
        $this->absContainer = $absContainer;
    }

    public function getUploader(): AbstractUploader
    {
        if (!empty($this->s3bucket) && !empty($this->awsRegion)) {
            return new S3Uploader($this->storageApiUrl, $this->s3bucket, $this->awsRegion);
        } elseif (!empty($this->absConnectionString) && !empty($this->absContainer)) {
            return new AbsUploader($this->storageApiUrl, $this->absConnectionString, $this->absContainer);
        } else {
            throw new RuntimeException('No uploader can be configured.');
        }
    }
}