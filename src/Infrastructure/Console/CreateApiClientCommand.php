<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Infrastructure\Security\ApiClient;
use App\Infrastructure\Security\ApiClientRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:api-client:create',
    description: 'Creates an API client and prints its API key (shown once, never stored in clear).',
)]
final class CreateApiClientCommand extends Command
{
    public function __construct(
        private readonly ApiClientRepository $clients,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Unique client name, e.g. "back-office" or "partner-acme"')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, \sprintf('Granted role(s): %s', implode(', ', ApiClient::ROLES)), [ApiClient::ROLE_READER])
            ->setHelp(implode("\n", [
                'The API key is printed once. Send it on every request:',
                '',
                '  Authorization: Bearer cs_...',
                '',
                'Roles are hierarchical: ROLE_ADMIN implies ROLE_OPERATOR, which implies ROLE_READER.',
            ]));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');
        $roles = $input->getOption('role');

        if (!\is_string($name) || !\is_array($roles)) {
            $io->error('Invalid arguments.');

            return Command::INVALID;
        }

        if (null !== $this->clients->findByName(trim($name))) {
            $io->error(\sprintf('An API client named "%s" already exists.', trim($name)));

            return Command::FAILURE;
        }

        try {
            [$client, $apiKey] = ApiClient::issue(
                id: Uuid::v7()->toRfc4122(),
                name: $name,
                roles: array_values(array_map(static fn (mixed $role): string => strtoupper(trim(\is_scalar($role) ? (string) $role : '')), $roles)),
            );
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        $this->clients->save($client);

        $io->success(\sprintf('API client "%s" created with roles %s.', $client->name(), implode(', ', $client->getRoles())));
        $io->writeln('API key (shown once, store it in your secret manager):');
        $io->writeln('');
        $io->writeln('  '.$apiKey);
        $io->writeln('');

        return Command::SUCCESS;
    }
}
