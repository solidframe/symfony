<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:domain-event', description: 'Create a new Domain Event class')]
final class MakeDomainEventCommand extends AbstractGeneratorCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->addOption('listener', null, InputOption::VALUE_NONE, 'Also generate a corresponding EventListener');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = parent::execute($input, $output);

        if ($result === self::SUCCESS && $input->getOption('listener')) {
            $name = $input->getArgument('name');
            $listenerCommand = $this->getApplication()->find('make:event-listener');

            $listenerInput = new \Symfony\Component\Console\Input\ArrayInput([
                'name' => $name . 'Listener',
                '--event-class' => $name,
            ]);

            $listenerCommand->run($listenerInput, $output);
        }

        return $result;
    }

    protected function getStubPath(): string
    {
        return __DIR__ . '/../../stubs/event.stub';
    }

    protected function getDefaultDirectory(): string
    {
        return getcwd() . '/src/Domain/Event';
    }

    protected function getTypeName(): string
    {
        return 'Domain Event';
    }
}
