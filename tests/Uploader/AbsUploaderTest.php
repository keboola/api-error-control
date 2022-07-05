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
        if (empty(getenv('AZURE_ABS_CONNECTION_STRING')) || empty(getenv('AZURE_ABS_CONTAINER'))) {
            throw new Exception('Environment variable AZURE_ABS_CONNECTION_STRING or AZURE_ABS_CONTAINER is empty.');
        }
    }

    public function testUploader(): void
    {
        $basePath = 'https://example.com/';
        $uploader = new AbsUploader(
            $basePath,
            (string) getenv('AZURE_ABS_CONNECTION_STRING'),
            (string) getenv('AZURE_ABS_CONTAINER')
        );
        $returnUrl = $uploader->upload('some content');
        self::assertStringStartsWith('https://example.com/admin/utils/logs', $returnUrl);
        preg_match('#\?file=(.*)#', $returnUrl, $matches);
        $blobClient = BlobRestProxy::createBlobService((string) getenv('AZURE_ABS_CONNECTION_STRING'));
        $blob = $blobClient->getBlob((string) getenv('AZURE_ABS_CONTAINER'), $matches[1]);
        self::assertEquals('some content', fread($blob->getContentStream(), 1000));
        self::assertEquals('text/html', $blob->getProperties()->getContentType());
    }
}
