<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:entity', description: 'Create a new Entity class')]
final class MakeEntityCommand extends AbstractGeneratorCommand
{
    protected function getStubPath(): string
    {
        return __DIR__ . '/../../stubs/entity.stub';
    }

    protected function getDefaultDirectory(): string
    {
        return getcwd() . '/src/Domain';
    }

    protected function getTypeName(): string
    {
        return 'Entity';
    }
}
