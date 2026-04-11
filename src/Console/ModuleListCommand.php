<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use SolidFrame\Modular\Registry\ModuleRegistryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'solidframe:module:list', description: 'List all registered modules')]
final class ModuleListCommand extends Command
{
    public function __construct(private readonly ModuleRegistryInterface $registry)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $modules = $this->registry->all();

        if ($modules === []) {
            $io->info('No modules registered.');

            return Command::SUCCESS;
        }

        $io->table(
            ['Name', 'Dependencies'],
            array_map(static fn($module): array => [
                $module->name(),
                implode(', ', $module->dependsOn()) ?: '-',
            ], $modules),
        );

        return Command::SUCCESS;
    }
}
