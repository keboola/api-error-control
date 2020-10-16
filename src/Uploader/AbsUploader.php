<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Uploader;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\Block;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

class AbsUploader extends AbstractUploader
{
    private const CHUNK_SIZE = 4 * 1024 * 1024;
    private const PADLENGTH = 5; // Size of the string used for the block ID, modify if needed.

    /**
     * @var string
     */
    private $absConnectionString;

    /**
     * @var string
     */
    private $absContainer;

    public function __construct(string $storageApiUrl, string $absConnectionString, string $absContainer)
    {
        parent::__construct($storageApiUrl);
        $this->absConnectionString = $absConnectionString;
        $this->absContainer = $absContainer;
    }

    public function upload(string $content, string $contentType = 'text/html'): string
    {
        $options = new CreateBlockBlobOptions();
        $fileName = $this->generateFilename($contentType);
        $options->setContentDisposition(sprintf('attachment; filename=%s', $fileName));
        $options->setContentType($contentType);
        $blobClient = BlobRestProxy::createBlobService($this->absConnectionString);
        $blobClient->createBlockBlob(
            $this->absContainer,
            $fileName,
            $content,
            $options
        );
        return $this->getUrl($fileName);
    }

    public function uploadFile(
        string $filePath,
        string $contentType = 'text/html'
    ): string {
        $options = new CreateBlockBlobOptions();
        $fileName = $this->generateFilename($contentType, $filePath);
        $options->setContentDisposition(sprintf('attachment; filename=%s', $fileName));
        $options->setContentType($contentType);
        $blobClient = BlobRestProxy::createBlobService($this->absConnectionString);
        /** @var resource $handle */
        $handle = fopen($filePath, 'rb');
        $counter = 1;
        $blockIds = [];
        try {
            while (!feof($handle)) {
                $blockId = base64_encode(str_pad((string) $counter, self::PADLENGTH, '0', STR_PAD_LEFT));
                $block = new Block();
                $block->setBlockId($blockId);
                $block->setType('Uncommitted');
                $blockIds[] = $block;
                /** @var string $data */
                $data = fread($handle, self::CHUNK_SIZE);
                // Upload the block.
                $blobClient->createBlobBlock($this->absContainer, $fileName, $blockId, $data);
                $counter++;
            }
            fclose($handle);
            $blobClient->commitBlobBlocks($this->absContainer, $fileName, $blockIds);
        } catch (ServiceException $e) {
            throw new \Exception(
                sprintf('Uploading file "%s" failed "%s".', $filePath, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        return $this->getUrl($fileName);
    }
}
