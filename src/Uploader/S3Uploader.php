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

    public function __construct(string $storageApiUrl, string $s3bucket, string $region)
    {
        parent::__construct($storageApiUrl);
        $this->s3bucket = $s3bucket;
        $this->region = $region;
    }

    private function getClient(): S3Client
    {
        return new S3Client([
            'version' => '2006-03-01',
            'retries' => 20,
            'region' => $this->region,
        ]);
    }

    public function upload(string $content, string $contentType = 'text/html'): string
    {
        /* intentionally don't create the client in ctor, it throws exceptions and these are hard to log
            during symfony application initialization. */
        $s3client = $this->getClient();
        $s3FileName = $this->generateFilename($contentType);
        $s3client->putObject([
            'Bucket' => $this->s3bucket,
            'Key' => $this->path . '/' . $s3FileName,
            'ContentType' => $contentType,
            'ACL' => 'private',
            'ServerSideEncryption' => 'AES256',
            'Body' => $content,
        ]);
        return $this->getUrl($s3FileName);
    }

    public function uploadFile(
        string $filePath,
        string $contentType = 'text/html'
    ): string {
        $s3client = $this->getClient();
        $s3FileName = $this->generateFilename($contentType, $filePath);

        $s3client->putObject([
            'Bucket' => $this->s3bucket,
            'Key' => $this->path . '/' . $s3FileName,
            'ContentType' => $contentType,
            'ACL' => 'private',
            'ServerSideEncryption' => 'AES256',
            'SourceFile' => $filePath,
        ]);

        return $this->getUrl($s3FileName);
    }
}
