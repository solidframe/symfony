<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\DependencyInjection;

use SolidFrame\Symfony\Cqrs\ContainerHandlerResolver;
use SolidFrame\Symfony\Discovery\HandlerDiscovery;
use SolidFrame\Symfony\EventDriven\ContainerListenerResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class HandlerDiscoveryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasParameter('solidframe.discovery.enabled')) {
            return;
        }

        if (! $container->getParameter('solidframe.discovery.enabled')) {
            return;
        }

        /** @var list<string> $paths */
        $paths = $container->getParameter('solidframe.discovery.paths');

        $projectDir = $container->getParameter('kernel.project_dir');
        $absolutePaths = array_map(
            static fn(string $path): string => $projectDir . '/' . $path,
            $paths,
        );

        $this->discoverCommandHandlers($container, $absolutePaths);
        $this->discoverQueryHandlers($container, $absolutePaths);
        $this->discoverEventListeners($container, $absolutePaths);
    }

    /**
     * @param list<string> $paths
     */
    private function discoverCommandHandlers(ContainerBuilder $container, array $paths): void
    {
        if (! $container->has('solidframe.command_handler_resolver')) {
            return;
        }

        if (! interface_exists(\SolidFrame\Cqrs\CommandHandler::class)) {
            return;
        }

        $handlers = HandlerDiscovery::within($paths, \SolidFrame\Cqrs\CommandHandler::class);

        if ($handlers === []) {
            return;
        }

        // Register handler classes as public services (container needs to resolve them)
        foreach ($handlers as $handlerClass) {
            if ($container->has($handlerClass)) {
                $container->getDefinition($handlerClass)->setPublic(true);
            } else {
                $container->register($handlerClass)
                    ->setAutowired(true)
                    ->setPublic(true);
            }
        }

        $definition = $container->getDefinition('solidframe.command_handler_resolver');
        $definition->setClass(ContainerHandlerResolver::class);
        $definition->replaceArgument(1, $handlers);
    }

    /**
     * @param list<string> $paths
     */
    private function discoverQueryHandlers(ContainerBuilder $container, array $paths): void
    {
        if (! $container->has('solidframe.query_handler_resolver')) {
            return;
        }

        if (! interface_exists(\SolidFrame\Cqrs\QueryHandler::class)) {
            return;
        }

        $handlers = HandlerDiscovery::within($paths, \SolidFrame\Cqrs\QueryHandler::class);

        if ($handlers === []) {
            return;
        }

        foreach ($handlers as $handlerClass) {
            if ($container->has($handlerClass)) {
                $container->getDefinition($handlerClass)->setPublic(true);
            } else {
                $container->register($handlerClass)
                    ->setAutowired(true)
                    ->setPublic(true);
            }
        }

        $definition = $container->getDefinition('solidframe.query_handler_resolver');
        $definition->setClass(ContainerHandlerResolver::class);
        $definition->replaceArgument(1, $handlers);
    }

    /**
     * @param list<string> $paths
     */
    private function discoverEventListeners(ContainerBuilder $container, array $paths): void
    {
        if (! $container->has('solidframe.listener_resolver')) {
            return;
        }

        if (! interface_exists(\SolidFrame\EventDriven\EventListener::class)) {
            return;
        }

        $listeners = HandlerDiscovery::listeners($paths, \SolidFrame\EventDriven\EventListener::class);

        if ($listeners === []) {
            return;
        }

        foreach ($listeners as $listenerClasses) {
            foreach ($listenerClasses as $listenerClass) {
                if ($container->has($listenerClass)) {
                    $container->getDefinition($listenerClass)->setPublic(true);
                } else {
                    $container->register($listenerClass)
                        ->setAutowired(true)
                        ->setPublic(true);
                }
            }
        }

        $definition = $container->getDefinition('solidframe.listener_resolver');
        $definition->setClass(ContainerListenerResolver::class);
        $definition->replaceArgument(1, $listeners);
    }
}
