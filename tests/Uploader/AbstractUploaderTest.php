<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Uploader;

use Keboola\ErrorControl\Uploader\AbstractUploader;
use PHPUnit\Framework\TestCase;

class AbstractUploaderTest extends TestCase
{
    public function testNonDefaultArguments(): void
    {
        $uploader = new class('https://api.url', 'local-log', '/prefix?file=') extends AbstractUploader {
            public function upload(
                string $content,
                string $contentType = 'text/html'
            ): string {
                return $this->storageApiUrl . '/' . $this->path . $this->urlPrefix;
            }
        };

        $this->assertEquals('https://api.url/local-log/prefix?file=', $uploader->upload('xxx'));
    }
}
