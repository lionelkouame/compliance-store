<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\Document;
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
    }

    public function testItStoresADocument(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => [
                    'ownerId' => 'usr_123456789',
                    'metadata' => [
                        'country' => 'FRA',
                        'retentionYears' => 5,
                        'category' => 'identity',
                    ],
                ],
                'files' => [
                    'file' => new UploadedFile(self::FIXTURE_PATH, 'passport.png', 'image/png', null, true),
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'ownerId' => 'usr_123456789',
            'metadata' => [
                'country' => 'FRA',
                'retentionYears' => '5',
                'category' => 'identity',
            ],
        ]);

        $data = $response->toArray();
        self::assertArrayNotHasKey('wrappedDataKey', $data);
        self::assertNotEmpty($data['fileHash']);
        self::assertNotEmpty($data['storageKey']);
    }

    public function testItRejectsAMissingFile(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => [
                    'ownerId' => 'usr_123456789',
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
