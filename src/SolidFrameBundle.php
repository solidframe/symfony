<?php

declare(strict_types=1);

namespace SolidFrame\Symfony;

use SolidFrame\Symfony\Cqrs\ContainerHandlerResolver;
use SolidFrame\Symfony\DependencyInjection\HandlerDiscoveryCompilerPass;
use SolidFrame\Symfony\EventDriven\ContainerListenerResolver;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SolidFrameBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new HandlerDiscoveryCompilerPass());
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('discovery')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->arrayNode('paths')
                            ->scalarPrototype()->end()
                            ->defaultValue(['src'])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cqrs')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('command_bus')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('middleware')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('query_bus')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('middleware')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('event_driven')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('event_bus')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('middleware')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('event_sourcing')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('event_store')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('driver')->defaultValue('in_memory')->end()
                                ->scalarNode('connection')->defaultNull()->end()
                                ->scalarNode('table')->defaultValue('event_store')->end()
                            ->end()
                        ->end()
                        ->arrayNode('snapshot_store')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('driver')->defaultValue('in_memory')->end()
                                ->scalarNode('connection')->defaultNull()->end()
                                ->scalarNode('table')->defaultValue('snapshots')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('saga')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('store')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('driver')->defaultValue('in_memory')->end()
                                ->scalarNode('connection')->defaultNull()->end()
                                ->scalarNode('table')->defaultValue('sagas')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('modular')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('path')->defaultValue('modules')->end()
                        ->booleanNode('auto_discovery')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        // Store discovery config as parameters for CompilerPass
        $builder->setParameter('solidframe.discovery.enabled', $config['discovery']['enabled']);
        $builder->setParameter('solidframe.discovery.paths', $config['discovery']['paths']);

        $this->registerCqrs($config, $builder);
        $this->registerEventDriven($config, $builder);
        $this->registerEventSourcing($config, $builder);
        $this->registerModular($builder);
        $this->registerSaga($config, $builder);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerCqrs(array $config, ContainerBuilder $builder): void
    {
        if (! class_exists(\SolidFrame\Cqrs\Bus\CommandBus::class)) {
            return;
        }

        $builder->register('solidframe.command_handler_resolver', ContainerHandlerResolver::class)
            ->setPublic(true)
            ->setArguments([new Reference('service_container'), []]);

        $builder->register(\SolidFrame\Core\Bus\CommandBusInterface::class)
            ->setClass(\SolidFrame\Cqrs\Bus\CommandBus::class)
            ->setPublic(true)
            ->setArguments([
                new Reference('solidframe.command_handler_resolver'),
                $config['cqrs']['command_bus']['middleware'],
            ]);

        $builder->register('solidframe.query_handler_resolver', ContainerHandlerResolver::class)
            ->setPublic(true)
            ->setArguments([new Reference('service_container'), []]);

        $builder->register(\SolidFrame\Core\Bus\QueryBusInterface::class)
            ->setClass(\SolidFrame\Cqrs\Bus\QueryBus::class)
            ->setPublic(true)
            ->setArguments([
                new Reference('solidframe.query_handler_resolver'),
                $config['cqrs']['query_bus']['middleware'],
            ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerEventDriven(array $config, ContainerBuilder $builder): void
    {
        if (! class_exists(\SolidFrame\EventDriven\Bus\EventBus::class)) {
            return;
        }

        $builder->register('solidframe.listener_resolver', ContainerListenerResolver::class)
            ->setPublic(true)
            ->setArguments([new Reference('service_container'), []]);

        $builder->register(\SolidFrame\Core\Bus\EventBusInterface::class)
            ->setClass(\SolidFrame\EventDriven\Bus\EventBus::class)
            ->setPublic(true)
            ->setArguments([
                new Reference('solidframe.listener_resolver'),
                $config['event_driven']['event_bus']['middleware'],
            ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerEventSourcing(array $config, ContainerBuilder $builder): void
    {
        if (! class_exists(\SolidFrame\EventSourcing\Store\InMemoryEventStore::class)) {
            return;
        }

        $eventStoreDriver = $config['event_sourcing']['event_store']['driver'];

        if ($eventStoreDriver === 'dbal') {
            $builder->register(\SolidFrame\EventSourcing\Store\EventStoreInterface::class)
                ->setClass(\SolidFrame\Symfony\EventSourcing\DbalEventStore::class)
                ->setPublic(true)
                ->setArguments([
                    new Reference('doctrine.dbal.default_connection'),
                    $config['event_sourcing']['event_store']['table'],
                ]);
        } else {
            $builder->register(\SolidFrame\EventSourcing\Store\EventStoreInterface::class)
                ->setClass(\SolidFrame\EventSourcing\Store\InMemoryEventStore::class)
                ->setPublic(true);
        }

        $snapshotDriver = $config['event_sourcing']['snapshot_store']['driver'];

        if ($snapshotDriver === 'dbal') {
            $builder->register(\SolidFrame\EventSourcing\Snapshot\SnapshotStoreInterface::class)
                ->setClass(\SolidFrame\Symfony\EventSourcing\DbalSnapshotStore::class)
                ->setPublic(true)
                ->setArguments([
                    new Reference('doctrine.dbal.default_connection'),
                    $config['event_sourcing']['snapshot_store']['table'],
                ]);
        } else {
            $builder->register(\SolidFrame\EventSourcing\Snapshot\SnapshotStoreInterface::class)
                ->setClass(\SolidFrame\EventSourcing\Snapshot\InMemorySnapshotStore::class)
                ->setPublic(true);
        }
    }

    private function registerModular(ContainerBuilder $builder): void
    {
        if (! class_exists(\SolidFrame\Modular\Registry\InMemoryModuleRegistry::class)) {
            return;
        }

        $builder->register(\SolidFrame\Modular\Registry\ModuleRegistryInterface::class)
            ->setClass(\SolidFrame\Modular\Registry\InMemoryModuleRegistry::class)
            ->setPublic(true);

        $builder->register(Console\ModuleListCommand::class)
            ->setAutoconfigured(true)
            ->setAutowired(true)
            ->addTag('console.command');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerSaga(array $config, ContainerBuilder $builder): void
    {
        if (! class_exists(\SolidFrame\Saga\Store\InMemorySagaStore::class)) {
            return;
        }

        $sagaDriver = $config['saga']['store']['driver'];

        if ($sagaDriver === 'dbal') {
            $builder->register(\SolidFrame\Saga\Store\SagaStoreInterface::class)
                ->setClass(\SolidFrame\Symfony\Saga\DbalSagaStore::class)
                ->setPublic(true)
                ->setArguments([
                    new Reference('doctrine.dbal.default_connection'),
                    $config['saga']['store']['table'],
                ]);
        } else {
            $builder->register(\SolidFrame\Saga\Store\SagaStoreInterface::class)
                ->setClass(\SolidFrame\Saga\Store\InMemorySagaStore::class)
                ->setPublic(true);
        }

        $builder->register(Console\SagaStatusCommand::class)
            ->setAutoconfigured(true)
            ->setAutowired(true)
            ->addTag('console.command');
    }
}
