<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Storage;

use App\Domain\ValueObject\Document\DocumentId;
use App\Infrastructure\Storage\MinioStorageGateway;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;

final class MinioStorageGatewayTest extends TestCase
{
    private const DOCUMENT_ID = '660e8400-e29b-41d4-a716-446655440000';

    private string $directory;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/minio-gateway-test-'.bin2hex(random_bytes(6));
        $this->filesystem = new Filesystem(new LocalFilesystemAdapter($this->directory));
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory('/');
        @rmdir($this->directory);
    }

    public function testDeleteRemovesTheStoredContent(): void
    {
        $gateway = new MinioStorageGateway($this->filesystem);
        $id = DocumentId::make(self::DOCUMENT_ID);

        $gateway->store($id, 'cipher-bytes');
        self::assertTrue($this->filesystem->fileExists('documents/'.self::DOCUMENT_ID));

        $gateway->delete($id);

        self::assertFalse($this->filesystem->fileExists('documents/'.self::DOCUMENT_ID));
    }

    public function testDeletingMissingContentIsNotAnError(): void
    {
        (new MinioStorageGateway($this->filesystem))->delete(DocumentId::make(self::DOCUMENT_ID));

        self::assertFalse($this->filesystem->fileExists('documents/'.self::DOCUMENT_ID));
    }
}
