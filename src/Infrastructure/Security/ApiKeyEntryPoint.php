<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Answer to a request that carries no API key at all: a 401 problem+json
 * body with the RFC 6750 challenge header, so clients learn how to authenticate.
 */
final readonly class ApiKeyEntryPoint implements AuthenticationEntryPointInterface
{
    public const string REALM = 'Compliance Store API';

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(
            [
                'title' => 'Unauthorized',
                'detail' => 'An API key is required: send it as "Authorization: Bearer <key>".',
                'status' => Response::HTTP_UNAUTHORIZED,
            ],
            Response::HTTP_UNAUTHORIZED,
            [
                'Content-Type' => 'application/problem+json',
                'WWW-Authenticate' => \sprintf('Bearer realm="%s"', self::REALM),
            ],
        );
    }
}
