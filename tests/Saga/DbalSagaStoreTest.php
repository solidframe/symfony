<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Tests\Saga;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SolidFrame\Saga\Saga\AbstractSaga;
use SolidFrame\Saga\State\Association;
use SolidFrame\Saga\State\SagaStatus;
use SolidFrame\Symfony\Saga\DbalSagaStore;

final class DbalSagaStoreTest extends TestCase
{
    private Connection $connection;
    private DbalSagaStore $store;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        $this->connection->executeStatement(
            file_get_contents(__DIR__ . '/../../config/schema/sagas.sql'),
        );

        $this->store = new DbalSagaStore($this->connection);
    }

    #[Test]
    public function savesAndFindsSaga(): void
    {
        $saga = new TestOrderSaga('saga-123');

        $this->store->save($saga);

        $loaded = $this->store->find('saga-123');

        self::assertNotNull($loaded);
        self::assertInstanceOf(TestOrderSaga::class, $loaded);
        self::assertSame('saga-123', $loaded->id());
        self::assertSame(SagaStatus::InProgress, $loaded->status());
    }

    #[Test]
    public function returnsNullForUnknownSaga(): void
    {
        self::assertNull($this->store->find('non-existent'));
    }

    #[Test]
    public function findsByAssociation(): void
    {
        $saga = new TestOrderSaga('saga-456');
        $saga->setOrderId('order-789');

        $this->store->save($saga);

        $found = $this->store->findByAssociation(
            TestOrderSaga::class,
            new Association('orderId', 'order-789'),
        );

        self::assertNotNull($found);
        self::assertSame('saga-456', $found->id());
    }

    #[Test]
    public function returnsNullWhenAssociationNotFound(): void
    {
        $saga = new TestOrderSaga('saga-456');
        $saga->setOrderId('order-789');

        $this->store->save($saga);

        $found = $this->store->findByAssociation(
            TestOrderSaga::class,
            new Association('orderId', 'wrong-id'),
        );

        self::assertNull($found);
    }

    #[Test]
    public function deletesSaga(): void
    {
        $saga = new TestOrderSaga('saga-to-delete');

        $this->store->save($saga);
        $this->store->delete('saga-to-delete');

        self::assertNull($this->store->find('saga-to-delete'));
    }

    #[Test]
    public function updatesSagaOnSecondSave(): void
    {
        $saga = new TestOrderSaga('saga-update');

        $this->store->save($saga);

        $saga->markCompleted();
        $this->store->save($saga);

        $loaded = $this->store->find('saga-update');

        self::assertSame(SagaStatus::Completed, $loaded->status());
    }
}

final class TestOrderSaga extends AbstractSaga
{
    public function setOrderId(string $orderId): void
    {
        $this->associateWith('orderId', $orderId);
    }

    public function markCompleted(): void
    {
        $this->complete();
    }
}
