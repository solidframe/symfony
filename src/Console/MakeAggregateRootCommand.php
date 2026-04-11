<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:aggregate-root', description: 'Create a new Aggregate Root class')]
final class MakeAggregateRootCommand extends AbstractGeneratorCommand
{
    protected function getStubPath(): string
    {
        return __DIR__ . '/../../stubs/aggregate-root.stub';
    }

    protected function getDefaultDirectory(): string
    {
        return getcwd() . '/src/Domain';
    }

    protected function getTypeName(): string
    {
        return 'Aggregate Root';
    }
}
