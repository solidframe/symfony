<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:event-listener', description: 'Create a new Event Listener class')]
final class MakeEventListenerCommand extends AbstractGeneratorCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->addOption('event-class', null, InputOption::VALUE_REQUIRED, 'The class name of the event this listener handles');
    }

    protected function getStubPath(): string
    {
        return __DIR__ . '/../../stubs/listener.stub';
    }

    protected function getDefaultDirectory(): string
    {
        return getcwd() . '/src/Application/Listener';
    }

    protected function getTypeName(): string
    {
        return 'Event Listener';
    }

    protected function replaceExtraPlaceholders(string $content, InputInterface $input): string
    {
        $eventClass = $input->getOption('event-class');

        if ($eventClass) {
            return str_replace('{{ event }}', $eventClass, $content);
        }

        return str_replace('{{ event }}', 'object', $content);
    }
}
