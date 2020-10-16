<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Uploader;

use Keboola\ErrorControl\Uploader\LocalFileUploader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class LocalFileUploaderTest extends TestCase
{
    /** @var string */
    private $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/test-local-uploader';
        (new Filesystem)->remove($this->dir);
        (new Filesystem)->mkdir($this->dir);
    }

    public function testUploader(): void
    {
        $uploader = new LocalFileUploader(
            'https:\\example.com',
            $this->dir
        );
        $result = $uploader->upload('some content');
        self::assertStringStartsWith($this->dir, $result);
        $savedContent = file_get_contents($result);
        self::assertEquals('some content', $savedContent);
    }

    public function testUploaderUploadFile(): void
    {
        $uploader = new LocalFileUploader(
            'https:\\example.com',
            $this->dir
        );
        $resultToMove = $uploader->upload('some content');
        $result = $uploader->uploadFile($resultToMove);
        self::assertNotEquals($resultToMove, $result);
        self::assertStringStartsWith($this->dir, $result);
        $savedContent = file_get_contents($result);
        self::assertEquals('some content', $savedContent);
    }
}
