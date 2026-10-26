<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Domain\Entity\AuditEvent;
use App\Domain\Entity\Document;
use App\Domain\Entity\Holder;
use App\Infrastructure\Security\ApiClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Reading documents back: metadata, listing per holder, decrypted download
 * with integrity check (ADR 0011).
 */
final class DocumentReadApiTest extends ApiTestCase
{
    use ApiClientFixture;

    protected static ?bool $alwaysBootKernel = true;

    private const FIXTURE_PATH = __DIR__.'/../http/passport.png';

    private string $storageDir;

    protected function setUp(): void
    {
        parent::setUp();
        self::clearApiClients();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.AuditEvent::class)->execute();
        $entityManager->createQuery('DELETE FROM '.Document::class)->execute();
        $entityManager->createQuery('DELETE FROM '.Holder::class)->execute();

        $this->storageDir = self::getContainer()->getParameter('kernel.project_dir').'/var/test_storage';
        (new Filesystem())->remove($this->storageDir);
    }

    public function testItReturnsTheMetadataOfAStoredDocument(): void
    {
        $client = self::createAuthenticatedClient();
        $holderId = $this->registerHolder($client);
        $stored = $this->storeDocument($client, $holderId);

        $response = $client->request('GET', '/api/v1/documents/'.$stored['id']);

        self::assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        self::assertSame($stored['id'], $data['id']);
        self::assertSame($holderId, $data['holderId']);
        self::assertSame(hash_file('sha256', self::FIXTURE_PATH), $data['fileHash']);
        self::assertArrayNotHasKey('wrappedDataKey', $data);
        self::assertArrayNotHasKey('file', $data);
    }

    public function testItListsTheDocumentsOfAHolderOldestFirst(): void
    {
        $client = self::createAuthenticatedClient();
        $holderId = $this->registerHolder($client);
        $first = $this->storeDocument($client, $holderId);
        $second = $this->storeDocument($client, $holderId);

        $response = $client->request('GET', '/api/v1/holders/'.$holderId.'/documents');

        self::assertResponseStatusCodeSame(200);
        $members = $response->toArray()['member'];
        self::assertCount(2, $members);
        self::assertSame([$first['id'], $second['id']], array_column($members, 'id'));

        $other = $this->registerHolder($client, 'Grace Hopper');
        $client->request('GET', '/api/v1/holders/'.$other.'/documents');
        self::assertResponseStatusCodeSame(200);
        self::assertSame([], $client->getResponse()->toArray()['member']);

        $client->request('GET', '/api/v1/holders/00000000-0000-0000-0000-000000000000/documents');
        self::assertResponseStatusCodeSame(404);
    }

    public function testItDownloadsTheDecryptedContentByteForByte(): void
    {
        $client = self::createAuthenticatedClient();
        $holderId = $this->registerHolder($client);
        $stored = $this->storeDocument($client, $holderId);

        $response = $client->request('GET', '/api/v1/documents/'.$stored['id'].'/content', [
            'headers' => ['Accept' => 'application/octet-stream'],
        ]);

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/octet-stream');
        self::assertResponseHeaderSame('cache-control', 'no-store, private');
        self::assertResponseHeaderSame('x-document-hash', $stored['fileHash']);
        self::assertStringContainsString('attachment', (string) $response->getHeaders()['content-disposition'][0]);
        self::assertSame(file_get_contents(self::FIXTURE_PATH), $response->getContent());
    }

    public function testADownloadNegotiatesOnlyOctetStream(): void
    {
        $client = self::createAuthenticatedClient();
        $holderId = $this->registerHolder($client);
        $stored = $this->storeDocument($client, $holderId);

        $client->request('GET', '/api/v1/documents/'.$stored['id'].'/content', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(406);
    }

    public function testATamperedCiphertextIsNeverReleased(): void
    {
        $client = self::createAuthenticatedClient();
        $holderId = $this->registerHolder($client);
        $stored = $this->storeDocument($client, $holderId);

        // Flip one byte of the stored ciphertext: authenticated encryption must reject it.
        $path = $this->storageDir.'/documents/'.$stored['id'];
        $bytes = file_get_contents($path);
        self::assertNotFalse($bytes);
        $bytes[\strlen($bytes) - 1] = \chr(\ord($bytes[\strlen($bytes) - 1]) ^ 0x01);
        file_put_contents($path, $bytes);

        $client->request('GET', '/api/v1/documents/'.$stored['id'].'/content', [
            'headers' => ['Accept' => 'application/octet-stream'],
        ]);

        self::assertResponseStatusCodeSame(502);
        self::assertStringNotContainsString('documents/', (string) $client->getResponse()->getContent(false));
    }

    public function testReadsReturn404ForUnknownOrMalformedIds(): void
    {
        $client = self::createAuthenticatedClient();

        $client->request('GET', '/api/v1/documents/00000000-0000-0000-0000-000000000000');
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', '/api/v1/documents/not-a-uuid');
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', '/api/v1/documents/00000000-0000-0000-0000-000000000000/content', [
            'headers' => ['Accept' => 'application/octet-stream'],
        ]);
        self::assertResponseStatusCodeSame(404);
    }

    public function testAReaderSeesMetadataButCannotDownloadContent(): void
    {
        $admin = self::createAuthenticatedClient();
        $holderId = $this->registerHolder($admin);
        $stored = $this->storeDocument($admin, $holderId);

        $reader = self::createAuthenticatedClient([ApiClient::ROLE_READER]);

        $reader->request('GET', '/api/v1/documents/'.$stored['id']);
        self::assertResponseStatusCodeSame(200);

        $reader->request('GET', '/api/v1/holders/'.$holderId.'/documents');
        self::assertResponseStatusCodeSame(200);

        $reader->request('GET', '/api/v1/documents/'.$stored['id'].'/content', [
            'headers' => ['Accept' => 'application/octet-stream'],
        ]);
        self::assertResponseStatusCodeSame(403);
    }

    private function registerHolder(Client $client, string $name = 'Ada Lovelace'): string
    {
        $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['holderName' => $name, 'birthDate' => '1990-01-01', 'email' => bin2hex(random_bytes(6)).'@example.com'],
        ]);
        self::assertResponseStatusCodeSame(201);

        return $client->getResponse()->toArray()['id'];
    }

    /**
     * @return array<string, mixed>
     */
    private function storeDocument(Client $client, string $holderId): array
    {
        $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['holderId' => $holderId],
                'files' => ['file' => new UploadedFile(self::FIXTURE_PATH, 'passport.png', 'image/png', null, true)],
            ],
        ]);
        self::assertResponseStatusCodeSame(201);

        return $client->getResponse()->toArray();
    }
}
