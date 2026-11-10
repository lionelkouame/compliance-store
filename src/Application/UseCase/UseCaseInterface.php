<?php

declare(strict_types=1);

namespace App\Application\UseCase;

/**
 * @template TRequest of UseCaseRequestInterface
 * @template TResponse of UseCaseResponseInterface
 */
interface UseCaseInterface
{
    /**
     * @param TRequest $request
     *
     * @return TResponse
     */
    public function execute(UseCaseRequestInterface $request): UseCaseResponseInterface;
}
