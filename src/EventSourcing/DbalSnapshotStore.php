<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\EventSourcing;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use SolidFrame\Core\Identity\IdentityInterface;
use SolidFrame\EventSourcing\Snapshot\Snapshot;
use SolidFrame\EventSourcing\Snapshot\SnapshotStoreInterface;

final readonly class DbalSnapshotStore implements SnapshotStoreInterface
{
    public function __construct(
        private Connection $connection,
        private string $table = 'snapshots',
    ) {}

    public function save(Snapshot $snapshot): void
    {
        $existing = $this->connection->fetchOne(
            "SELECT aggregate_id FROM {$this->table} WHERE aggregate_id = ?",
            [$snapshot->aggregateId],
        );

        if ($existing !== false) {
            $this->connection->update($this->table, [
                'aggregate_type' => $snapshot->aggregateType,
                'version' => $snapshot->version,
                'state' => json_encode($snapshot->state, JSON_THROW_ON_ERROR),
            ], ['aggregate_id' => $snapshot->aggregateId]);
        } else {
            $this->connection->insert($this->table, [
                'aggregate_id' => $snapshot->aggregateId,
                'aggregate_type' => $snapshot->aggregateType,
                'version' => $snapshot->version,
                'state' => json_encode($snapshot->state, JSON_THROW_ON_ERROR),
                'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function load(IdentityInterface $aggregateId): ?Snapshot
    {
        $row = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE aggregate_id = ?",
            [$aggregateId->value()],
        );

        if ($row === false) {
            return null;
        }

        return new Snapshot(
            aggregateId: $row['aggregate_id'],
            aggregateType: $row['aggregate_type'],
            version: (int) $row['version'],
            state: json_decode((string) $row['state'], true, 512, JSON_THROW_ON_ERROR),
        );
    }
}
