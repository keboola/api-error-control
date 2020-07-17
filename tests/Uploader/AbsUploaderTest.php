<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Uploader;

use Exception;
use Keboola\ErrorControl\Uploader\AbsUploader;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use PHPUnit\Framework\TestCase;

class AbsUploaderTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (empty(getenv('ABS_CONNECTION_STRING')) || empty(getenv('ABS_CONTAINER'))) {
            throw new Exception('Environment variable ABS_CONNECTION_STRING or ABS_CONTAINER is empty.');
        }
    }

    public function testUploader(): void
    {
        $basePath = 'https:\\\\example.com';
        $uploader = new AbsUploader(
            $basePath,
            (string) getenv('ABS_CONNECTION_STRING'),
            (string) getenv('ABS_CONTAINER')
        );
        $returnUrl = $uploader->upload('some content');
        self::assertStringStartsWith($basePath, $returnUrl);
        preg_match('#\?file=(.*)#', $returnUrl, $matches);
        $blobClient = BlobRestProxy::createBlobService((string) getenv('ABS_CONNECTION_STRING'));
        $blob = $blobClient->getBlob((string) getenv('ABS_CONTAINER'), $matches[1]);
        self::assertEquals('some content', fread($blob->getContentStream(), 1000));
        self::assertEquals('text/html', $blob->getProperties()->getContentType());
    }
}
