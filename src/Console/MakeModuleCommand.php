<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'make:module', description: 'Create a new module with ServiceProvider and Module class')]
final class MakeModuleCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the module');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $modulesPath = getcwd() . '/modules';
        $modulePath = $modulesPath . '/' . $name;

        if (is_dir($modulePath)) {
            $io->error(sprintf('Module [%s] already exists.', $name));

            return Command::FAILURE;
        }

        $namespace = 'App\\Modules\\' . $name;
        $moduleName = strtolower((string) $name);

        mkdir($modulePath, 0o755, true);

        // Generate Module class
        $moduleStub = file_get_contents(__DIR__ . '/../../stubs/module-class.stub');
        $moduleContent = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ moduleName }}'],
            [$namespace, $name, $moduleName],
            $moduleStub,
        );
        file_put_contents($modulePath . '/' . $name . 'Module.php', $moduleContent);

        $io->success(sprintf('Module [%s] created successfully.', $name));
        $io->listing([
            $modulePath . '/' . $name . 'Module.php',
        ]);

        return Command::SUCCESS;
    }
}
