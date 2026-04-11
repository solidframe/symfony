<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SolidFrame\Core\Bus\CommandBusInterface;
use SolidFrame\Core\Bus\EventBusInterface;
use SolidFrame\Core\Bus\QueryBusInterface;
use SolidFrame\Cqrs\Bus\CommandBus;
use SolidFrame\Cqrs\Bus\QueryBus;
use SolidFrame\EventDriven\Bus\EventBus;
use SolidFrame\EventSourcing\Snapshot\SnapshotStoreInterface;
use SolidFrame\EventSourcing\Store\EventStoreInterface;
use SolidFrame\Modular\Registry\ModuleRegistryInterface;
use SolidFrame\Saga\Store\SagaStoreInterface;

final class SolidFrameBundleTest extends TestCase
{
    private TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new TestKernel('test', true);
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    #[Test]
    public function bindsCommandBusInterface(): void
    {
        $bus = $this->kernel->getContainer()->get(CommandBusInterface::class);

        self::assertInstanceOf(CommandBus::class, $bus);
    }

    #[Test]
    public function bindsQueryBusInterface(): void
    {
        $bus = $this->kernel->getContainer()->get(QueryBusInterface::class);

        self::assertInstanceOf(QueryBus::class, $bus);
    }

    #[Test]
    public function bindsEventBusInterface(): void
    {
        $bus = $this->kernel->getContainer()->get(EventBusInterface::class);

        self::assertInstanceOf(EventBus::class, $bus);
    }

    #[Test]
    public function bindsEventStoreInterface(): void
    {
        $store = $this->kernel->getContainer()->get(EventStoreInterface::class);

        self::assertInstanceOf(\SolidFrame\EventSourcing\Store\InMemoryEventStore::class, $store);
    }

    #[Test]
    public function bindsSnapshotStoreInterface(): void
    {
        $store = $this->kernel->getContainer()->get(SnapshotStoreInterface::class);

        self::assertInstanceOf(\SolidFrame\EventSourcing\Snapshot\InMemorySnapshotStore::class, $store);
    }

    #[Test]
    public function bindsModuleRegistryInterface(): void
    {
        $registry = $this->kernel->getContainer()->get(ModuleRegistryInterface::class);

        self::assertInstanceOf(\SolidFrame\Modular\Registry\InMemoryModuleRegistry::class, $registry);
    }

    #[Test]
    public function bindsSagaStoreInterface(): void
    {
        $store = $this->kernel->getContainer()->get(SagaStoreInterface::class);

        self::assertInstanceOf(\SolidFrame\Saga\Store\InMemorySagaStore::class, $store);
    }
}
