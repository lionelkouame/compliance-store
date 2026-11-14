<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Domain\Entity\Document;
use App\Domain\Entity\Holder;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\ValueObject\Document\DocumentId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class DocumentApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private const FIXTURE_PATH = __DIR__.'/../http/passport.png';

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.Document::class)->execute();
        $entityManager->createQuery('DELETE FROM '.Holder::class)->execute();
    }

    public function testItStoresADocumentForAnExistingHolder(): void
    {
        $client = static::createClient();
        $holderId = $this->createHolder($client);

        $data = $this->storeDocument($client, ['holderId' => $holderId])->toArray();

        self::assertResponseStatusCodeSame(201);
        self::assertSame($holderId, $data['holderId']);
        self::assertArrayNotHasKey('wrappedDataKey', $data);
        self::assertArrayNotHasKey('storageKey', $data);
        self::assertNotEmpty($data['fileHash']);
    }

    public function testItPersistsTheHolderOfTheDocument(): void
    {
        $client = static::createClient();
        $holderId = $this->createHolder($client);

        $data = $this->storeDocument($client, ['holderId' => $holderId])->toArray();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $document = $entityManager->find(Document::class, DocumentId::make($data['id']));

        self::assertInstanceOf(Document::class, $document);
        self::assertSame($holderId, $document->holderId()->value());
    }

    public function testAFailedDatabaseWriteLeavesNoOrphanContentInStorage(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $holderId = $this->createHolder($client);
        $storageDirectory = self::getContainer()->getParameter('kernel.project_dir').'/var/test_storage/documents';
        $before = is_dir($storageDirectory) ? \count(scandir($storageDirectory)) : 0;

        $failingRepository = $this->createStub(DocumentRepositoryInterface::class);
        $failingRepository->method('add')->willThrowException(new \RuntimeException('Database unavailable.'));
        self::getContainer()->set(DocumentRepositoryInterface::class, $failingRepository);

        $this->storeDocument($client, ['holderId' => $holderId]);

        self::assertResponseStatusCodeSame(500);
        self::assertSame($before, is_dir($storageDirectory) ? \count(scandir($storageDirectory)) : 0);
    }

    public function testItRejectsAMissingFile(): void
    {
        static::createClient()->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAMissingHolderId(): void
    {
        $this->storeDocument(static::createClient(), []);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAMalformedHolderId(): void
    {
        $this->storeDocument(static::createClient(), ['holderId' => 'not-a-uuid']);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAnUnknownHolder(): void
    {
        $response = $this->storeDocument(static::createClient(), ['holderId' => '0192f0c1-0000-7000-8000-0000000000aa']);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('The holder of the document does not exist.', $response->getContent(false));
    }

    /**
     * @param array<string, string> $parameters
     */
    private function storeDocument(Client $client, array $parameters): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        return $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => $parameters,
                'files' => [
                    'file' => new UploadedFile(self::FIXTURE_PATH, 'passport.png', 'image/png', null, true),
                ],
            ],
        ]);
    }

    private function createHolder(Client $client): string
    {
        $data = $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['holderName' => 'Jane Doe', 'birthDate' => '1990-01-01', 'email' => 'jane.documents@example.com'],
        ])->toArray();

        return $data['identifier'];
    }
}
