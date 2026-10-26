<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiClient>
 */
final class ApiClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiClient::class);
    }

    /**
     * Looks a client up by its clear API key. Revoked clients are not returned.
     */
    public function findActiveByApiKey(#[\SensitiveParameter] string $apiKey): ?ApiClient
    {
        return $this->findOneBy(['keyHash' => ApiClient::hash($apiKey), 'revokedAt' => null]);
    }

    public function findByName(string $name): ?ApiClient
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function save(ApiClient $client): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($client);
        $entityManager->flush();
    }
}
