<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Domain\Entity\Document;
use App\Domain\Entity\Holder;
use App\Infrastructure\Security\ApiClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * GDPR art. 17 workflow: erasure request, then anonymization with
 * crypto-shredding of the holder's documents.
 */
final class HolderErasureApiTest extends ApiTestCase
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
        $entityManager->createQuery('DELETE FROM '.Document::class)->execute();
        $entityManager->createQuery('DELETE FROM '.Holder::class)->execute();

        $this->storageDir = self::getContainer()->getParameter('kernel.project_dir').'/var/test_storage';
        (new Filesystem())->remove($this->storageDir);
    }

    public function testTheFullErasureWorkflowDestroysDocumentsAndAnonymizesTheHolder(): void
    {
        $client = self::createAuthenticatedClient();
        $holderId = $this->registerHolder($client);
        $documentId = $this->storeDocument($client, $holderId);

        self::assertFileExists($this->storageDir.'/documents/'.$documentId);

        $client->request('POST', '/api/v1/holders/'.$holderId.'/erasure-request');
        self::assertResponseStatusCodeSame(200);
        $flagged = $client->getResponse()->toArray();
        self::assertSame('erasure_requested', $flagged['status']);
        self::assertNotNull($flagged['erasureRequestedAt']);
        self::assertSame('Ada Lovelace', $flagged['holderName'], 'Nothing is erased at request time.');

        $client->request('POST', '/api/v1/holders/'.$holderId.'/anonymization');
        self::assertResponseStatusCodeSame(200);
        $anonymized = $client->getResponse()->toArray();
        self::assertSame('anonymized', $anonymized['status']);
        self::assertSame(Holder::ANONYMIZED_NAME, $anonymized['holderName']);
        self::assertNull($anonymized['birthDate']);
        self::assertNull($anonymized['email']);
        self::assertNotNull($anonymized['anonymizedAt']);
        self::assertSame($holderId, $anonymized['id']);

        // Ciphertext gone from storage, record (and wrapped key) gone from the database.
        self::assertFileDoesNotExist($this->storageDir.'/documents/'.$documentId);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertSame(0, (int) $entityManager->createQuery('SELECT count(d) FROM '.Document::class.' d')->getSingleScalarResult());

        // The anonymized record is what a later read returns.
        $client->request('GET', '/api/v1/holders/'.$holderId);
        self::assertResponseStatusCodeSame(200);
        self::assertSame(Holder::ANONYMIZED_NAME, $client->getResponse()->toArray()['holderName']);

        // No new personal data may be collected for this data subject.
        $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['holderId' => $holderId],
                'files' => ['file' => new UploadedFile(self::FIXTURE_PATH, 'passport.png', 'image/png', null, true)],
            ],
        ]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testAnErasureRequestCannotBeRecordedTwice(): void
    {
        $client = self::createAuthenticatedClient();
        $holderId = $this->registerHolder($client);

        $client->request('POST', '/api/v1/holders/'.$holderId.'/erasure-request');
        self::assertResponseStatusCodeSame(200);

        $client->request('POST', '/api/v1/holders/'.$holderId.'/erasure-request');
        self::assertResponseStatusCodeSame(409);
    }

    public function testAnonymizationRequiresAPendingErasureRequest(): void
    {
        $client = self::createAuthenticatedClient();
        $holderId = $this->registerHolder($client);

        $client->request('POST', '/api/v1/holders/'.$holderId.'/anonymization');
        self::assertResponseStatusCodeSame(409);

        $client->request('GET', '/api/v1/holders/'.$holderId);
        self::assertSame('active', $client->getResponse()->toArray()['status']);
    }

    public function testErasureEndpointsReturn404ForAnUnknownHolder(): void
    {
        $client = self::createAuthenticatedClient();

        $client->request('POST', '/api/v1/holders/00000000-0000-0000-0000-000000000000/erasure-request');
        self::assertResponseStatusCodeSame(404);

        $client->request('POST', '/api/v1/holders/not-a-uuid/anonymization');
        self::assertResponseStatusCodeSame(404);
    }

    public function testErasureIsReservedToAdministrators(): void
    {
        $admin = self::createAuthenticatedClient();
        $holderId = $this->registerHolder($admin);

        $operator = self::createAuthenticatedClient([ApiClient::ROLE_OPERATOR]);
        $operator->request('POST', '/api/v1/holders/'.$holderId.'/erasure-request');
        self::assertResponseStatusCodeSame(403);

        $operator->request('POST', '/api/v1/holders/'.$holderId.'/anonymization');
        self::assertResponseStatusCodeSame(403);
    }

    private function registerHolder(Client $client): string
    {
        $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['holderName' => 'Ada Lovelace', 'birthDate' => '1990-01-01', 'email' => bin2hex(random_bytes(6)).'@example.com'],
        ]);
        self::assertResponseStatusCodeSame(201);

        return $client->getResponse()->toArray()['id'];
    }

    private function storeDocument(Client $client, string $holderId): string
    {
        $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['holderId' => $holderId],
                'files' => ['file' => new UploadedFile(self::FIXTURE_PATH, 'passport.png', 'image/png', null, true)],
            ],
        ]);
        self::assertResponseStatusCodeSame(201);

        return $client->getResponse()->toArray()['id'];
    }
}
