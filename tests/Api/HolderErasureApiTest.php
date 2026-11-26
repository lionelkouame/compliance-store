<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Domain\Entity\Document;
use App\Domain\Entity\Holder;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\ValueObject\Shared\Identifier\DocumentId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Droit à l'effacement (RGPD art. 17) de bout en bout : demande, puis
 * anonymisation du titulaire et suppression de ses documents (issue #88).
 */
final class HolderErasureApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private const string FIXTURE_PATH = __DIR__.'/../http/passport.png';

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.Document::class)->execute();
        $entityManager->createQuery('DELETE FROM '.Holder::class)->execute();
    }

    public function testTheFullErasureRemovesEveryPersonalDataAndTheDocuments(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $holderId = $this->createHolder($client);
        $documentId = $this->storeDocument($client, $holderId);

        $requested = $client->request('POST', '/api/v1/holders/'.$holderId.'/erasure-request')->toArray();
        self::assertResponseStatusCodeSame(200);
        self::assertSame('erasure_requested', $requested['status']);
        self::assertNotNull($requested['erasureRequestedAt']);

        $anonymized = $client->request('POST', '/api/v1/holders/'.$holderId.'/anonymization')->toArray();
        self::assertResponseStatusCodeSame(200);
        self::assertSame('anonymized', $anonymized['status']);
        self::assertSame(Holder::ANONYMIZED_NAME, $anonymized['holderName']);
        self::assertSame($holderId.'@anonymized.invalid', $anonymized['email']);
        self::assertSame('1990-01-01', $anonymized['birthDate']);
        self::assertNotNull($anonymized['anonymizedAt']);

        // L'anonymisation est bien persistée.
        $reloaded = $client->request('GET', '/api/v1/holders/'.$holderId)->toArray();
        self::assertSame('anonymized', $reloaded['status']);
        self::assertSame($holderId.'@anonymized.invalid', $reloaded['email']);

        // L'ancien email ne permet plus de retrouver la personne.
        $client->request('POST', '/api/v1/holders/search', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['email' => 'jane.erasure@example.com'],
        ]);
        self::assertResponseStatusCodeSame(404);

        // Le document a disparu : ni ligne (donc plus de clé de données), ni contenu chiffré.
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        self::assertNull($entityManager->find(Document::class, DocumentId::make($documentId)));

        $this->expectException(\RuntimeException::class);
        self::getContainer()->get(StorageGatewayInterface::class)->read(DocumentId::make($documentId));
    }

    public function testAnErasureCannotBeRequestedTwice(): void
    {
        $client = static::createClient();
        $holderId = $this->createHolder($client);

        $client->request('POST', '/api/v1/holders/'.$holderId.'/erasure-request');
        self::assertResponseStatusCodeSame(200);

        $client->request('POST', '/api/v1/holders/'.$holderId.'/erasure-request');
        self::assertResponseStatusCodeSame(422);
    }

    public function testAHolderCannotBeAnonymizedWithoutAPendingRequest(): void
    {
        $client = static::createClient();
        $holderId = $this->createHolder($client);

        $client->request('POST', '/api/v1/holders/'.$holderId.'/anonymization');
        self::assertResponseStatusCodeSame(422);

        $holder = $client->request('GET', '/api/v1/holders/'.$holderId)->toArray();
        self::assertSame('active', $holder['status']);
        self::assertSame('jane.erasure@example.com', $holder['email']);
    }

    public function testAnUnknownHolderIsNotFound(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/holders/01a17b0e-e559-7fc6-b4e1-4b96eaa3262c/erasure-request');
        self::assertResponseStatusCodeSame(404);

        $client->request('POST', '/api/v1/holders/not-a-uuid/anonymization');
        self::assertResponseStatusCodeSame(404);
    }

    private function createHolder(Client $client): string
    {
        $data = $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['holderName' => 'Jane Doe', 'birthDate' => '1990-07-14', 'email' => 'jane.erasure@example.com'],
        ])->toArray();

        return $data['identifier'];
    }

    private function storeDocument(Client $client, string $holderId): string
    {
        $data = $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['holderId' => $holderId],
                'files' => [
                    'file' => new UploadedFile(self::FIXTURE_PATH, 'passport.png', 'image/png', null, true),
                ],
            ],
        ])->toArray();

        return $data['id'];
    }
}
