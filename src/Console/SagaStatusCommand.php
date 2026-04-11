<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use SolidFrame\Saga\State\Association;
use SolidFrame\Saga\Store\SagaStoreInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'solidframe:saga:status', description: 'Show the status of a saga by ID')]
final class SagaStatusCommand extends Command
{
    public function __construct(private readonly SagaStoreInterface $store)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'The saga ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $saga = $this->store->find($input->getArgument('id'));

        if ($saga === null) {
            $io->error(sprintf('Saga not found: %s', $input->getArgument('id')));

            return Command::FAILURE;
        }

        $io->table(
            ['Property', 'Value'],
            [
                ['ID', $saga->id()],
                ['Type', $saga::class],
                ['Status', $saga->status()->name],
                ['Associations', implode(', ', array_map(
                    static fn(Association $a): string => "{$a->key}={$a->value}",
                    $saga->associations(),
                ))],
            ],
        );

        return Command::SUCCESS;
    }
}
