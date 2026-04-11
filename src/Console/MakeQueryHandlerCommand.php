<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:query-handler', description: 'Create a new CQRS Query Handler class')]
final class MakeQueryHandlerCommand extends AbstractGeneratorCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->addOption('query-class', null, InputOption::VALUE_REQUIRED, 'The class name of the query this handler handles');
    }

    protected function getStubPath(): string
    {
        return __DIR__ . '/../../stubs/query-handler.stub';
    }

    protected function getDefaultDirectory(): string
    {
        return getcwd() . '/src/Application/Query';
    }

    protected function getTypeName(): string
    {
        return 'Query Handler';
    }

    protected function replaceExtraPlaceholders(string $content, InputInterface $input): string
    {
        $queryClass = $input->getOption('query-class');

        if ($queryClass) {
            return str_replace('{{ query }}', $queryClass, $content);
        }

        return str_replace('{{ query }}', 'object', $content);
    }
}
