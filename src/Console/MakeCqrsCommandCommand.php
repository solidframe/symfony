<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:cqrs-command', description: 'Create a new CQRS Command class')]
final class MakeCqrsCommandCommand extends AbstractGeneratorCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->addOption('handler', null, InputOption::VALUE_NONE, 'Also generate the corresponding CommandHandler');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = parent::execute($input, $output);

        if ($result === self::SUCCESS && $input->getOption('handler')) {
            $name = $input->getArgument('name');
            $handlerCommand = $this->getApplication()->find('make:command-handler');

            $handlerInput = new \Symfony\Component\Console\Input\ArrayInput([
                'name' => $name . 'Handler',
                '--command-class' => $name,
            ]);

            $handlerCommand->run($handlerInput, $output);
        }

        return $result;
    }

    protected function getStubPath(): string
    {
        return __DIR__ . '/../../stubs/command.stub';
    }

    protected function getDefaultDirectory(): string
    {
        return getcwd() . '/src/Application/Command';
    }

    protected function getTypeName(): string
    {
        return 'Command';
    }
}
