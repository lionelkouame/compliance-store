<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Resolves "Authorization: Bearer <api key>" to an {@see ApiClient}
 * (security.yaml, firewall "api", authenticator "access_token").
 */
final readonly class ApiKeyTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private ApiClientRepository $clients,
    ) {}

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $client = $this->clients->findActiveByApiKey($accessToken);

        if (null === $client || !$client->matches($accessToken)) {
            // Same message for unknown and revoked keys: do not tell an attacker which one it is.
            throw new BadCredentialsException('Invalid API key.');
        }

        return new UserBadge($client->getUserIdentifier(), static fn (): ApiClient => $client);
    }
}
