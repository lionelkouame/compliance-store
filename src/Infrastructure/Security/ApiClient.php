<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A machine client allowed to call the API, authenticated with a bearer
 * API key (RFC 6750). Only the SHA-256 hash of the key is stored: the clear
 * key is shown once, when the client is created (app:api-client:create).
 *
 * Roles form a hierarchy (security.yaml): ROLE_ADMIN > ROLE_OPERATOR > ROLE_READER.
 * This is an infrastructure concern: the domain knows nothing about callers.
 */
final class ApiClient implements UserInterface
{
    public const string ROLE_READER = 'ROLE_READER';
    public const string ROLE_OPERATOR = 'ROLE_OPERATOR';
    public const string ROLE_ADMIN = 'ROLE_ADMIN';

    /** @var list<string> */
    public const array ROLES = [self::ROLE_READER, self::ROLE_OPERATOR, self::ROLE_ADMIN];

    private const string KEY_PREFIX = 'cs_';

    /**
     * @param non-empty-string $name
     * @param list<string>     $roles
     */
    private function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly string $keyHash,
        private readonly array $roles,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $revokedAt,
    ) {}

    /**
     * Generates a fresh API key and returns the client together with the
     * clear key. The key is never stored nor recoverable afterwards.
     *
     * @param list<string> $roles
     *
     * @return array{0: self, 1: string} [client, clear API key]
     */
    public static function issue(string $id, string $name, array $roles): array
    {
        $name = trim($name);

        if ('' === $name) {
            throw new \InvalidArgumentException('An API client name cannot be empty.');
        }

        if (mb_strlen($name) > 100) {
            throw new \InvalidArgumentException('An API client name cannot exceed 100 characters.');
        }

        $roles = array_values(array_unique($roles));

        if ([] === $roles) {
            throw new \InvalidArgumentException('An API client needs at least one role.');
        }

        foreach ($roles as $role) {
            if (!\in_array($role, self::ROLES, true)) {
                throw new \InvalidArgumentException(\sprintf('Unknown role "%s". Expected one of: %s.', $role, implode(', ', self::ROLES)));
            }
        }

        // 32 random bytes, URL-safe, prefixed so a leaked key is recognisable in logs and scanners.
        $apiKey = self::KEY_PREFIX.rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        $client = new self(
            id: $id,
            name: $name,
            keyHash: self::hash($apiKey),
            roles: $roles,
            createdAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
            revokedAt: null,
        );

        return [$client, $apiKey];
    }

    public static function hash(#[\SensitiveParameter] string $apiKey): string
    {
        return hash('sha256', $apiKey);
    }

    /**
     * Constant-time comparison of a clear API key against the stored hash.
     */
    public function matches(#[\SensitiveParameter] string $apiKey): bool
    {
        return hash_equals($this->keyHash, self::hash($apiKey));
    }

    public function revoke(): void
    {
        $this->revokedAt ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function isRevoked(): bool
    {
        return null !== $this->revokedAt;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function revokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'roles' => $this->roles, 'keyHash' => '***'];
    }
}
