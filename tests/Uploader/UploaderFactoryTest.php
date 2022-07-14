<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Uploader;

use Keboola\ErrorControl\Uploader\AbsUploader;
use Keboola\ErrorControl\Uploader\LocalFileUploader;
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
            (string) getenv('AWS_S3_LOGS_BUCKET'),
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
            (string) getenv('AZURE_ABS_CONNECTION_STRING'),
            (string) getenv('AZURE_ABS_CONTAINER')
        );
        self::assertInstanceOf(AbsUploader::class, $factory->getUploader());
    }

    public function testGetLocalFileUploader(): void
    {
        $factory = new UploaderFactory(
            'https:\\example.com',
            '',
            '',
            '',
            '',
            '/home/keboola'
        );
        self::assertInstanceOf(LocalFileUploader::class, $factory->getUploader());
    }

    public function testNoUploader(): void
    {
        $factory = new UploaderFactory('https:\\example.com');
        self::assertNull($factory->getUploader());
    }
}
