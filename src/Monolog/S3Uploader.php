<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Monolog;

use Aws\S3\S3Client;

class S3Uploader
{
    /**
     * @var string
     */
    private $urlPrefix = '/admin/utils/logs?file=';

    /**
     * @var string
     */
    private $s3bucket;

    /**
     * @var string
     */
    private $s3path = 'debug-files';

    /**
     * @var string
     */
    private $storageApiUrl;

    /**
     * @var string
     */
    private $region;

    public function __construct(string $storageApiUrl, string $s3bucket, string $region)
    {
        $this->storageApiUrl = $storageApiUrl;
        $this->s3bucket = $s3bucket;
        $this->region = $region;
    }

    private function getFileName() : string
    {
        return date('Y/m/d/H/') . date('Y-m-d-H-i-s') . '-' . uniqid() . '-log.html';
    }

    private function withUrlPrefix(string $logFileName) : string
    {
        return $this->storageApiUrl . '/' . $this->urlPrefix . $logFileName;
    }

    public function uploadToS3(string $content) : string
    {
        /* intentionally don't create the client in ctor, it throws exceptions and these are hard to log
            during symfony application initialization. */
        $s3client = new S3Client([
            'version' => '2006-03-01',
            'retries' => 20,
            'region' => $this->region,
        ]);
        $s3FileName = $this->getFileName();
        $s3client->putObject([
            'Bucket' => $this->s3bucket,
            'Key' => $this->s3path . '/' . $s3FileName,
            'ContentType' => 'text/html',
            'ACL' => 'private',
            'ServerSideEncryption' => 'AES256',
            'Body' => $content,
        ]);

        return $this->withUrlPrefix($s3FileName);
    }
}
