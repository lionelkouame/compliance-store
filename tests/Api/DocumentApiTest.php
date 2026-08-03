<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\Document;
use App\Domain\Entity\RegulatoryScope;
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
        $entityManager->createQuery('DELETE FROM '.RegulatoryScope::class)->execute();
    }

    public function testItStoresACompliantDocument(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/regulatory-scopes', [
            'json' => [
                'code' => 'KYC_INDIVIDUAL',
                'label' => "Vérification d'Identité Particulier",
                'description' => 'KYC',
                'allowedDocumentTypes' => ['PASSPORT'],
            ],
        ]);
        self::assertResponseStatusCodeSame(201);

        $response = $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => [
                    'documentType' => 'PASSPORT',
                    'ownerId' => 'usr_123456789',
                    'country' => 'FRA',
                    'retentionYears' => '5',
                ],
                'files' => [
                    'file' => new UploadedFile(self::FIXTURE_PATH, 'passport.png', 'image/png', null, true),
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'documentType' => 'PASSPORT',
            'ownerId' => 'usr_123456789',
            'country' => 'FRA',
            'retentionYears' => 5,
        ]);

        $data = $response->toArray();
        self::assertArrayNotHasKey('wrappedDataKey', $data);
        self::assertNotEmpty($data['fileHash']);
        self::assertNotEmpty($data['storageKey']);
    }

    public function testItRejectsADocumentTypeNotCoveredByAnyActiveScope(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => [
                    'documentType' => 'UNKNOWN_TYPE',
                    'ownerId' => 'usr_123456789',
                    'country' => 'FRA',
                    'retentionYears' => '5',
                ],
                'files' => [
                    'file' => new UploadedFile(self::FIXTURE_PATH, 'passport.png', 'image/png', null, true),
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAMissingFile(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => [
                    'documentType' => 'PASSPORT',
                    'ownerId' => 'usr_123456789',
                    'country' => 'FRA',
                    'retentionYears' => '5',
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
