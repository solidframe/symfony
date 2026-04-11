<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:saga', description: 'Create a new Saga class')]
final class MakeSagaCommand extends AbstractGeneratorCommand
{
    protected function getStubPath(): string
    {
        return __DIR__ . '/../../stubs/saga.stub';
    }

    protected function getDefaultDirectory(): string
    {
        return getcwd() . '/src/Application/Saga';
    }

    protected function getTypeName(): string
    {
        return 'Saga';
    }
}
