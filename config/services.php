<?php

declare(strict_types=1);

use SolidFrame\Symfony\Console\ModuleListCommand;
use SolidFrame\Symfony\Console\SagaStatusCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autoconfigure()
        ->autowire();

    // Register console commands, excluding those that depend on optional packages
    $services->load('SolidFrame\\Symfony\\Console\\', '../src/Console/')
        ->exclude([
            '../src/Console/ModuleListCommand.php',
            '../src/Console/SagaStatusCommand.php',
        ])
        ->tag('console.command');
};
