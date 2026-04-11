<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Tests\EventSourcing;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SolidFrame\Core\Event\DomainEventInterface;
use SolidFrame\Core\Identity\UuidIdentity;
use SolidFrame\EventSourcing\Exception\ConcurrencyException;
use SolidFrame\Symfony\EventSourcing\DbalEventStore;

final class DbalEventStoreTest extends TestCase
{
    private Connection $connection;
    private DbalEventStore $store;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        $this->connection->executeStatement(
            file_get_contents(__DIR__ . '/../../config/schema/event_store.sql'),
        );

        $this->store = new DbalEventStore($this->connection);
    }

    #[Test]
    public function persistsAndLoadsEvents(): void
    {
        $aggregateId = UuidIdentity::generate();
        $event = new TestEvent('order-created');

        $this->store->persist($aggregateId, 0, [$event]);

        $loaded = $this->store->load($aggregateId);

        self::assertCount(1, $loaded);
        self::assertInstanceOf(TestEvent::class, $loaded[0]);
        self::assertSame('order-created', $loaded[0]->name);
    }

    #[Test]
    public function loadsEventsInVersionOrder(): void
    {
        $aggregateId = UuidIdentity::generate();

        $this->store->persist($aggregateId, 0, [new TestEvent('first')]);
        $this->store->persist($aggregateId, 1, [new TestEvent('second')]);
        $this->store->persist($aggregateId, 2, [new TestEvent('third')]);

        $loaded = $this->store->load($aggregateId);

        self::assertCount(3, $loaded);
        self::assertSame('first', $loaded[0]->name);
        self::assertSame('second', $loaded[1]->name);
        self::assertSame('third', $loaded[2]->name);
    }

    #[Test]
    public function loadsEventsFromVersion(): void
    {
        $aggregateId = UuidIdentity::generate();

        $this->store->persist($aggregateId, 0, [
            new TestEvent('first'),
            new TestEvent('second'),
            new TestEvent('third'),
        ]);

        $loaded = $this->store->loadFromVersion($aggregateId, 1);

        self::assertCount(2, $loaded);
        self::assertSame('second', $loaded[0]->name);
        self::assertSame('third', $loaded[1]->name);
    }

    #[Test]
    public function throwsOnConcurrencyConflict(): void
    {
        $aggregateId = UuidIdentity::generate();

        $this->store->persist($aggregateId, 0, [new TestEvent('first')]);

        $this->expectException(ConcurrencyException::class);

        $this->store->persist($aggregateId, 0, [new TestEvent('conflict')]);
    }

    #[Test]
    public function returnsEmptyArrayForUnknownAggregate(): void
    {
        self::assertSame([], $this->store->load(UuidIdentity::generate()));
    }

    #[Test]
    public function preservesEventOccurredAt(): void
    {
        $aggregateId = UuidIdentity::generate();
        $occurredAt = new DateTimeImmutable('2026-01-15 10:30:00');
        $event = new TestEvent('test', $occurredAt);

        $this->store->persist($aggregateId, 0, [$event]);

        $loaded = $this->store->load($aggregateId);

        self::assertSame(
            $occurredAt->format('Y-m-d H:i:s'),
            $loaded[0]->occurredAt()->format('Y-m-d H:i:s'),
        );
    }
}

final readonly class TestEvent implements DomainEventInterface
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public string $name,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
    }

    public function eventName(): string
    {
        return $this->name;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
