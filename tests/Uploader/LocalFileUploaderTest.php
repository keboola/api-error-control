<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Uploader;

use Keboola\ErrorControl\Uploader\LocalFileUploader;
use PHPUnit\Framework\TestCase;

class LocalFileUploaderTest extends TestCase
{
    public function testUploader(): void
    {
        $uploader = new LocalFileUploader(
            'https:\\example.com',
            __DIR__
        );
        $result = $uploader->upload('some content');
        self::assertStringStartsWith(__DIR__, $result);
        $savedContent = file_get_contents($result);
        self::assertEquals('some content', $savedContent);
    }
}
