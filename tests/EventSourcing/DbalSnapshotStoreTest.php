<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Tests\EventSourcing;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SolidFrame\Core\Identity\UuidIdentity;
use SolidFrame\EventSourcing\Snapshot\Snapshot;
use SolidFrame\Symfony\EventSourcing\DbalSnapshotStore;

final class DbalSnapshotStoreTest extends TestCase
{
    private Connection $connection;
    private DbalSnapshotStore $store;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        $this->connection->executeStatement(
            file_get_contents(__DIR__ . '/../../config/schema/snapshots.sql'),
        );

        $this->store = new DbalSnapshotStore($this->connection);
    }

    #[Test]
    public function savesAndLoadsSnapshot(): void
    {
        $aggregateId = UuidIdentity::generate();

        $snapshot = new Snapshot(
            aggregateId: $aggregateId->value(),
            aggregateType: 'App\\Domain\\Order',
            version: 5,
            state: ['status' => 'confirmed', 'total' => 1500],
        );

        $this->store->save($snapshot);

        $loaded = $this->store->load($aggregateId);

        self::assertNotNull($loaded);
        self::assertSame($aggregateId->value(), $loaded->aggregateId);
        self::assertSame(5, $loaded->version);
        self::assertSame(['status' => 'confirmed', 'total' => 1500], $loaded->state);
    }

    #[Test]
    public function returnsNullForUnknownAggregate(): void
    {
        self::assertNull($this->store->load(UuidIdentity::generate()));
    }

    #[Test]
    public function updatesExistingSnapshot(): void
    {
        $aggregateId = UuidIdentity::generate();

        $this->store->save(new Snapshot($aggregateId->value(), 'Order', 5, ['v' => 1]));
        $this->store->save(new Snapshot($aggregateId->value(), 'Order', 10, ['v' => 2]));

        $loaded = $this->store->load($aggregateId);

        self::assertSame(10, $loaded->version);
        self::assertSame(['v' => 2], $loaded->state);
    }
}
