<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\AuditEvent;
use App\Domain\Entity\Document;
use App\Domain\Entity\Holder;
use App\Infrastructure\Security\ApiClient;
use App\Infrastructure\Security\ApiClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AuditEventApiTest extends ApiTestCase
{
    use ApiClientFixture;

    protected static ?bool $alwaysBootKernel = true;

    private const FIXTURE_PATH = __DIR__.'/../http/passport.png';

    protected function setUp(): void
    {
        parent::setUp();
        self::clearApiClients();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.AuditEvent::class)->execute();
        $entityManager->createQuery('DELETE FROM '.Document::class)->execute();
        $entityManager->createQuery('DELETE FROM '.Holder::class)->execute();
        (new Filesystem())->remove(self::getContainer()->getParameter('kernel.project_dir').'/var/test_storage');
    }

    public function testEveryPersonalDataActionLeavesATraceWithItsActor(): void
    {
        $apiKey = self::issueApiKey([ApiClient::ROLE_ADMIN]);
        $repository = self::getContainer()->get(ApiClientRepository::class);
        \assert($repository instanceof ApiClientRepository);
        $actor = $repository->findActiveByApiKey($apiKey)?->name();
        self::assertNotNull($actor);

        $client = static::createClient([], ['headers' => ['Authorization' => 'Bearer '.$apiKey]]);

        $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['holderName' => 'Ada Lovelace', 'birthDate' => '1990-01-01', 'email' => bin2hex(random_bytes(6)).'@example.com'],
        ]);
        $holderId = $client->getResponse()->toArray()['id'];

        $client->request('POST', '/api/v1/documents', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['holderId' => $holderId],
                'files' => ['file' => new UploadedFile(self::FIXTURE_PATH, 'passport.png', 'image/png', null, true)],
            ],
        ]);
        $documentId = $client->getResponse()->toArray()['id'];

        $client->request('GET', '/api/v1/documents/'.$documentId.'/content', ['headers' => ['Accept' => 'application/octet-stream']]);
        self::assertResponseStatusCodeSame(200);

        $client->request('POST', '/api/v1/holders/'.$holderId.'/erasure-request');
        $client->request('POST', '/api/v1/holders/'.$holderId.'/anonymization');
        self::assertResponseStatusCodeSame(200);

        $events = $client->request('GET', '/api/v1/audit-events')->toArray()['member'];

        self::assertSame(
            ['holder_anonymized', 'holder_erasure_requested', 'document_consulted', 'document_stored', 'holder_registered'],
            array_column($events, 'action'),
            'Newest first.',
        );
        self::assertSame([$actor], array_values(array_unique(array_column($events, 'actor'))));
        self::assertSame([$holderId, $holderId, $documentId, $documentId, $holderId], array_column($events, 'subjectId'));

        $forDocument = $client->request('GET', '/api/v1/audit-events?subjectId='.$documentId)->toArray()['member'];
        self::assertSame(['document_consulted', 'document_stored'], array_column($forDocument, 'action'));

        $limited = $client->request('GET', '/api/v1/audit-events?limit=2')->toArray()['member'];
        self::assertCount(2, $limited);
    }

    public function testTheTrailIsReservedToAdministrators(): void
    {
        self::createAuthenticatedClient([ApiClient::ROLE_OPERATOR])->request('GET', '/api/v1/audit-events');

        self::assertResponseStatusCodeSame(403);
    }
}
