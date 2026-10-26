<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Infrastructure\Security\ApiClientRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:api-client:revoke',
    description: 'Revokes an API client: its key stops working immediately.',
)]
final class RevokeApiClientCommand extends Command
{
    public function __construct(
        private readonly ApiClientRepository $clients,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the client to revoke');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $name = \is_string($name) ? trim($name) : '';

        $client = '' === $name ? null : $this->clients->findByName($name);

        if (null === $client) {
            $io->error(\sprintf('No API client named "%s".', $name));

            return Command::FAILURE;
        }

        if ($client->isRevoked()) {
            $io->warning(\sprintf('API client "%s" was already revoked on %s.', $client->name(), $client->revokedAt()?->format(\DateTimeInterface::ATOM) ?? '?'));

            return Command::SUCCESS;
        }

        $client->revoke();
        $this->clients->save($client);

        $io->success(\sprintf('API client "%s" revoked.', $client->name()));

        return Command::SUCCESS;
    }
}
