<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Uploader;

use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use Keboola\ErrorControl\Uploader\S3Uploader;
use PHPUnit\Framework\TestCase;

class S3UploaderTest extends TestCase
{
    private const BASE_PATH = 'https:\\example.com';

    public function setUp(): void
    {
        parent::setUp();
        if (empty(getenv('S3_LOGS_BUCKET')) || empty(getenv('AWS_DEFAULT_REGION'))) {
            throw new \Exception('Environment variable S3_LOGS_BUCKET or AWS_DEFAULT_REGION is empty.');
        }
    }

    public function testUploader(): void
    {
        $uploader = $this->getUploader();
        $result = $uploader->upload('some content');
        self::assertStringStartsWith(self::BASE_PATH, $result);
        $this->assertFileInS3($result);
    }

    public function testUploadFile(): void
    {
        $fileToUpload = __DIR__ . '/test-up.html';
        file_put_contents($fileToUpload, 'some content');
        $uploader = $this->getUploader();
        $result = $uploader->uploadFile($fileToUpload);
        $this->assertFileInS3($result);
    }

    private function getUploader(): S3Uploader
    {
        return new S3Uploader(
            self::BASE_PATH,
            (string) getenv('S3_LOGS_BUCKET'),
            (string) getenv('AWS_DEFAULT_REGION')
        );
    }

    private function assertFileInS3(string $filePath): void
    {
        $s3client = new S3Client([
            'version' => '2006-03-01',
            'retries' => 20,
            'region' => getenv('AWS_DEFAULT_REGION'),
        ]);
        $s3Path = 'debug-files/' . substr($filePath, strlen(self::BASE_PATH) + 24);
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
