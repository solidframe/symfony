<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractGeneratorCommand extends Command
{
    abstract protected function getStubPath(): string;

    abstract protected function getDefaultDirectory(): string;

    abstract protected function getTypeName(): string;

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, sprintf('The name of the %s', $this->getTypeName()));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        $directory = $this->getDefaultDirectory();
        $className = basename(str_replace('\\', '/', $name));
        $subDir = dirname(str_replace('\\', '/', $name));
        $namespace = $this->buildNamespace($directory, $subDir);

        $targetDir = $directory . ($subDir !== '.' ? '/' . $subDir : '');

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0o755, true);
        }

        $targetFile = $targetDir . '/' . $className . '.php';

        if (file_exists($targetFile)) {
            $io->error(sprintf('%s already exists: %s', $this->getTypeName(), $targetFile));

            return Command::FAILURE;
        }

        $stub = file_get_contents($this->getStubPath());
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $className],
            $stub,
        );

        $content = $this->replaceExtraPlaceholders($content, $input);

        file_put_contents($targetFile, $content);

        $io->success(sprintf('%s created: %s', $this->getTypeName(), $targetFile));

        return Command::SUCCESS;
    }

    protected function replaceExtraPlaceholders(string $content, InputInterface $input): string
    {
        return $content;
    }

    private function buildNamespace(string $directory, string $subDir): string
    {
        // Convert directory path to namespace: src/Domain -> App\Domain
        $relative = str_replace(getcwd() . '/src/', '', $directory);
        $namespace = 'App\\' . str_replace('/', '\\', $relative);

        if ($subDir !== '.') {
            $namespace .= '\\' . str_replace('/', '\\', $subDir);
        }

        return $namespace;
    }
}
