<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Saga;

use Doctrine\DBAL\Connection;
use SolidFrame\Saga\Saga\SagaInterface;
use SolidFrame\Saga\State\Association;
use SolidFrame\Saga\Store\SagaStoreInterface;

final readonly class DbalSagaStore implements SagaStoreInterface
{
    public function __construct(
        private Connection $connection,
        private string $table = 'sagas',
    ) {}

    public function find(string $id): ?SagaInterface
    {
        $row = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id],
        );

        if ($row === false) {
            return null;
        }

        return unserialize($row['state']);
    }

    public function findByAssociation(string $sagaClass, Association $association): ?SagaInterface
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT * FROM {$this->table} WHERE saga_type = ?",
            [$sagaClass],
        );

        foreach ($rows as $row) {
            $associations = json_decode((string) $row['associations'], true, 512, JSON_THROW_ON_ERROR);

            foreach ($associations as $assoc) {
                if ($assoc['key'] === $association->key && $assoc['value'] === $association->value) {
                    return unserialize($row['state']);
                }
            }
        }

        return null;
    }

    public function save(SagaInterface $saga): void
    {
        $associations = array_map(
            static fn(Association $a): array => ['key' => $a->key, 'value' => $a->value],
            $saga->associations(),
        );

        $data = [
            'saga_type' => $saga::class,
            'status' => $saga->status()->name,
            'associations' => json_encode($associations, JSON_THROW_ON_ERROR),
            'state' => serialize($saga),
        ];

        $existing = $this->connection->fetchOne(
            "SELECT id FROM {$this->table} WHERE id = ?",
            [$saga->id()],
        );

        if ($existing !== false) {
            $this->connection->update($this->table, $data, ['id' => $saga->id()]);
        } else {
            $data['id'] = $saga->id();
            $this->connection->insert($this->table, $data);
        }
    }

    public function delete(string $id): void
    {
        $this->connection->delete($this->table, ['id' => $id]);
    }
}
