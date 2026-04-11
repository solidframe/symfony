<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autoconfigure()
        ->autowire();

    // Register all console commands
    $services->load('SolidFrame\\Symfony\\Console\\', '../src/Console/')
        ->tag('console.command');
};
