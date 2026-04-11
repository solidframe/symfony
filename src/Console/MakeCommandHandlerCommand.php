<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:command-handler', description: 'Create a new CQRS Command Handler class')]
final class MakeCommandHandlerCommand extends AbstractGeneratorCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->addOption('command-class', null, InputOption::VALUE_REQUIRED, 'The class name of the command this handler handles');
    }

    protected function getStubPath(): string
    {
        return __DIR__ . '/../../stubs/command-handler.stub';
    }

    protected function getDefaultDirectory(): string
    {
        return getcwd() . '/src/Application/Command';
    }

    protected function getTypeName(): string
    {
        return 'Command Handler';
    }

    protected function replaceExtraPlaceholders(string $content, InputInterface $input): string
    {
        $commandClass = $input->getOption('command-class');

        if ($commandClass) {
            return str_replace('{{ command }}', $commandClass, $content);
        }

        return str_replace('{{ command }}', 'object', $content);
    }
}
