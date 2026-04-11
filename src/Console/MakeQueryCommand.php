<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:query', description: 'Create a new CQRS Query class')]
final class MakeQueryCommand extends AbstractGeneratorCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->addOption('handler', null, InputOption::VALUE_NONE, 'Also generate the corresponding QueryHandler');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = parent::execute($input, $output);

        if ($result === self::SUCCESS && $input->getOption('handler')) {
            $name = $input->getArgument('name');
            $handlerCommand = $this->getApplication()->find('make:query-handler');

            $handlerInput = new \Symfony\Component\Console\Input\ArrayInput([
                'name' => $name . 'Handler',
                '--query-class' => $name,
            ]);

            $handlerCommand->run($handlerInput, $output);
        }

        return $result;
    }

    protected function getStubPath(): string
    {
        return __DIR__ . '/../../stubs/query.stub';
    }

    protected function getDefaultDirectory(): string
    {
        return getcwd() . '/src/Application/Query';
    }

    protected function getTypeName(): string
    {
        return 'Query';
    }
}
