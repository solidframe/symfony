<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\EventSourcing;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use SolidFrame\Core\Event\DomainEventInterface;
use SolidFrame\Core\Identity\IdentityInterface;
use SolidFrame\EventSourcing\Exception\ConcurrencyException;
use SolidFrame\EventSourcing\Store\EventStoreInterface;

final readonly class DbalEventStore implements EventStoreInterface
{
    public function __construct(
        private Connection $connection,
        private string $table = 'event_store',
    ) {}

    public function persist(IdentityInterface $aggregateId, int $expectedVersion, array $events): void
    {
        $id = $aggregateId->value();

        $currentVersion = (int) ($this->connection->fetchOne(
            "SELECT MAX(version) FROM {$this->table} WHERE aggregate_id = ?",
            [$id],
        ) ?? 0);

        if ($currentVersion !== $expectedVersion) {
            throw ConcurrencyException::forAggregate($id, $expectedVersion, $currentVersion);
        }

        $version = $expectedVersion;

        foreach ($events as $event) {
            $version++;

            $this->connection->insert($this->table, [
                'aggregate_id' => $id,
                'aggregate_type' => $event::class,
                'version' => $version,
                'event_type' => $event::class,
                'payload' => json_encode($this->serializeEvent($event), JSON_THROW_ON_ERROR),
                'occurred_at' => $event->occurredAt()->format('Y-m-d H:i:s.u'),
            ]);
        }
    }

    public function load(IdentityInterface $aggregateId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT * FROM {$this->table} WHERE aggregate_id = ? ORDER BY version",
            [$aggregateId->value()],
        );

        return array_map($this->deserializeEvent(...), $rows);
    }

    public function loadFromVersion(IdentityInterface $aggregateId, int $fromVersion): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT * FROM {$this->table} WHERE aggregate_id = ? AND version > ? ORDER BY version",
            [$aggregateId->value(), $fromVersion],
        );

        return array_map($this->deserializeEvent(...), $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEvent(DomainEventInterface $event): array
    {
        $reflection = new ReflectionClass($event);
        $data = [];

        foreach ($reflection->getProperties() as $prop) {
            $value = $prop->getValue($event);

            if ($value instanceof DateTimeImmutable) {
                $value = $value->format('Y-m-d H:i:s.u');
            }

            $data[$prop->getName()] = $value;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function deserializeEvent(array $row): DomainEventInterface
    {
        /** @var class-string<DomainEventInterface> $eventClass */
        $eventClass = $row['event_type'];
        $payload = json_decode((string) $row['payload'], true, 512, JSON_THROW_ON_ERROR);

        $reflection = new ReflectionClass($eventClass);
        $instance = $reflection->newInstanceWithoutConstructor();

        foreach ($payload as $property => $value) {
            if (! $reflection->hasProperty($property)) {
                continue;
            }

            $prop = $reflection->getProperty($property);
            $prop->setValue($instance, $this->castPropertyValue($prop, $value));
        }

        return $instance;
    }

    private function castPropertyValue(ReflectionProperty $prop, mixed $value): mixed
    {
        $type = $prop->getType();

        if ($type instanceof ReflectionNamedType && $type->getName() === DateTimeImmutable::class && is_string($value)) {
            return new DateTimeImmutable($value);
        }

        return $value;
    }
}
