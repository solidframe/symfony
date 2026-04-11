<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:value-object', description: 'Create a new Value Object class')]
final class MakeValueObjectCommand extends AbstractGeneratorCommand
{
    protected function getStubPath(): string
    {
        return __DIR__ . '/../../stubs/value-object.stub';
    }

    protected function getDefaultDirectory(): string
    {
        return getcwd() . '/src/Domain';
    }

    protected function getTypeName(): string
    {
        return 'Value Object';
    }
}
