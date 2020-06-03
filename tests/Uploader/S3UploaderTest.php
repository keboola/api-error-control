<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Uploader;

use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use Keboola\ErrorControl\Uploader\S3Uploader;
use PHPUnit\Framework\TestCase;

class S3UploaderTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (empty(getenv('S3_LOGS_BUCKET')) || empty(getenv('AWS_DEFAULT_REGION'))) {
            throw new \Exception('Environment variable S3_LOGS_BUCKET or AWS_DEFAULT_REGION is empty.');
        }
    }

    public function testUploader(): void
    {
        $basePath = 'https:\\example.com';
        $uploader = new S3Uploader(
            $basePath,
            (string) getenv('S3_LOGS_BUCKET'),
            (string) getenv('AWS_DEFAULT_REGION')
        );
        $result = $uploader->upload('some content');
        self::assertStringStartsWith($basePath, $result);
        $s3client = new S3Client([
            'version' => '2006-03-01',
            'retries' => 20,
            'region' => getenv('AWS_DEFAULT_REGION'),
        ]);
        $s3Path = 'debug-files/' . substr($result, strlen($basePath) + 24);
        $obj = $s3client->getObject([
            'Bucket' => getenv('S3_LOGS_BUCKET'),
            'Key' => $s3Path,
        ])->toArray();
        self::assertEquals('text/html', $obj['ContentType']);
        self::assertArrayHasKey('Body', $obj);
        /** @var Stream $body */
        $body = $obj['Body'];
        self::assertEquals('some content', $body->read((int) $body->getSize()));
        $s3client->deleteObject([
            'Bucket' => getenv('S3_LOGS_BUCKET'),
            'Key' => $s3Path,
        ]);
    }
}
