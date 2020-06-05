<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Uploader;

use Keboola\ErrorControl\Uploader\AbsUploader;
use Keboola\ErrorControl\Uploader\S3Uploader;
use Keboola\ErrorControl\Uploader\UploaderFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class UploaderFactoryTest extends TestCase
{
    public function testGetS3Uploader(): void
    {
        $factory = new UploaderFactory(
            'https:\\example.com',
            (string) getenv('S3_LOGS_BUCKET'),
            (string) getenv('AWS_DEFAULT_REGION')
        );
        self::assertInstanceOf(S3Uploader::class, $factory->getUploader());
    }

    public function testGetAbsUploader(): void
    {
        $factory = new UploaderFactory(
            'https:\\example.com',
            '',
            '',
            (string) getenv('ABS_CONNECTION_STRING'),
            (string) getenv('ABS_CONTAINER')
        );
        self::assertInstanceOf(AbsUploader::class, $factory->getUploader());
    }

    public function testInvalid(): void
    {
        $factory = new UploaderFactory('https:\\example.com');
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('No uploader can be configured: s3Bucket: "NULL", s3Region: "NULL", ' .
            'absConnectionString: "NULL", absContainer: "NULL"');
        $factory->getUploader();
    }
}
