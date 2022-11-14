<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Uploader;

use Aws\S3\S3Client;

class S3Uploader extends AbstractUploader
{
    /**
     * @var string
     */
    private $s3bucket;

    /**
     * @var string
     */
    private $region;

    private const CONNECT_TIMEOUT = 10;
    private const CONNECT_RETRIES = 5;
    private const TRANSFER_TIMEOUT = 120;

    public function __construct(string $storageApiUrl, string $s3bucket, string $region)
    {
        parent::__construct($storageApiUrl);
        $this->s3bucket = $s3bucket;
        $this->region = $region;
    }

    public function upload(string $content, string $contentType = 'text/html'): string
    {
        /* intentionally don't create the client in ctor, it throws exceptions and these are hard to log
            during symfony application initialization. */
        $s3client = new S3Client([
            'version' => '2006-03-01',
            'retries' => self::CONNECT_RETRIES,
            'region' => $this->region,
            'http' => [
                'connect_timeout' => self::CONNECT_TIMEOUT,
                'timeout' => self::TRANSFER_TIMEOUT,
            ],
        ]);
        $s3FileName = $this->generateFilename($contentType);
        $s3client->putObject([
            'Bucket' => $this->s3bucket,
            'Key' => $this->path . '/' . $s3FileName,
            'ContentType' => $contentType,
            'ACL' => 'bucket-owner-full-control',
            'ServerSideEncryption' => 'AES256',
            'Body' => $content,
        ]);
        return $this->getUrl($s3FileName);
    }
}
