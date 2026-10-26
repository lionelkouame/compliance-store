<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\Document;
use App\Domain\Entity\Holder;
use App\Domain\ValueObject\BirthDate;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\HolderId;
use App\Domain\ValueObject\HolderName;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class DocumentApiTest extends ApiTestCase
{
    use ApiClientFixture;

    protected static ?bool $alwaysBootKernel = true;

    private const FIXTURE_PATH = __DIR__.'/../http/passport.png';

    private const HOLDER_ID = '770e8400-e29b-41d4-a716-446655440111';

    private const UNKNOWN_HOLDER_ID = '00000000-0000-0000-0000-000000000000';

    protected function setUp(): void
    {
        parent::setUp();
        self::clearApiClients();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.Document::class)->execute();
        $entityManager->createQuery('DELETE FROM '.Holder::class)->execute();

        // A document can only be stored for a registered holder.
        $entityManager->persist(Holder::register(
            id: HolderId::fromString(self::HOLDER_ID),
            name: new HolderName('Ada Lovelace'),
            birthDate: new BirthDate(new \DateTimeImmutable('1990-01-01', new \DateTimeZone('UTC'))),
            email: new Email('ada.lovelace@example.com'),
        ));
        $entityManager->flush();
        $entityManager->clear();

        // The test env writes ciphertexts to a local directory (flysystem.yaml): start from an empty one.
        (new Filesystem())->remove(self::getContainer()->getParameter('kernel.project_dir').'/var/test_storage');
    }

    public function testItStoresADocument(): void
    {
        $client = self::createAuthenticatedClient();

        $response = $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['holderId' => self::HOLDER_ID],
                'files' => [
                    'file' => new UploadedFile(self::FIXTURE_PATH, 'passport.png', 'image/png', null, true),
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(201);

        $data = $response->toArray();
        self::assertArrayNotHasKey('wrappedDataKey', $data);
        self::assertArrayNotHasKey('storageKey', $data);
        self::assertNotEmpty($data['fileHash']);
        self::assertSame(self::HOLDER_ID, $data['holderId']);
    }

    public function testItRejectsAMissingFile(): void
    {
        $client = self::createAuthenticatedClient();

        $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['holderId' => self::HOLDER_ID],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAMissingHolderId(): void
    {
        $client = self::createAuthenticatedClient();

        $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => [
                    'file' => new UploadedFile(self::FIXTURE_PATH, 'passport.png', 'image/png', null, true),
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAnUnknownHolder(): void
    {
        $client = self::createAuthenticatedClient();

        $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['holderId' => self::UNKNOWN_HOLDER_ID],
                'files' => [
                    'file' => new UploadedFile(self::FIXTURE_PATH, 'passport.png', 'image/png', null, true),
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAFileWhoseContentIsNotAnAllowedType(): void
    {
        $client = self::createAuthenticatedClient();
        $path = tempnam(sys_get_temp_dir(), 'doc');
        self::assertNotFalse($path);
        file_put_contents($path, 'plain text pretending to be a picture');

        try {
            $response = $client->request('POST', '/api/v1/documents', [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => ['holderId' => self::HOLDER_ID],
                    'files' => [
                        // The client-declared PNG name and type are ignored: the content is sniffed.
                        'file' => new UploadedFile($path, 'passport.png', 'image/png', null, true),
                    ],
                ],
            ]);

            self::assertResponseStatusCodeSame(422);
            self::assertSame('file', $response->toArray(false)['violations'][0]['propertyPath']);
            self::assertStringContainsString('not accepted', $response->toArray(false)['violations'][0]['message']);
        } finally {
            @unlink($path);
        }
    }

    public function testItRejectsAFileLargerThanTheConfiguredLimit(): void
    {
        $client = self::createAuthenticatedClient();
        $path = tempnam(sys_get_temp_dir(), 'doc');
        self::assertNotFalse($path);
        // A real PNG (valid signature) padded above DOCUMENT_MAX_SIZE=300k (.env.test).
        file_put_contents($path, file_get_contents(self::FIXTURE_PATH).str_repeat("\0", 200 * 1024));

        try {
            $response = $client->request('POST', '/api/v1/documents', [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => ['holderId' => self::HOLDER_ID],
                    'files' => [
                        'file' => new UploadedFile($path, 'passport.png', 'image/png', null, true),
                    ],
                ],
            ]);

            self::assertResponseStatusCodeSame(422);
            self::assertSame('file', $response->toArray(false)['violations'][0]['propertyPath']);
            self::assertStringContainsString('too large', $response->toArray(false)['violations'][0]['message']);
        } finally {
            @unlink($path);
        }
    }
}
