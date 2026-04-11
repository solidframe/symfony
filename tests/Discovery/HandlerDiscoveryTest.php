<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Tests\Discovery;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SolidFrame\Cqrs\CommandHandler;
use SolidFrame\Cqrs\QueryHandler;
use SolidFrame\EventDriven\EventListener;
use SolidFrame\Symfony\Discovery\HandlerDiscovery;
use SolidFrame\Symfony\Tests\Discovery\Fixtures\CreateOrderCommand;
use SolidFrame\Symfony\Tests\Discovery\Fixtures\CreateOrderHandler;
use SolidFrame\Symfony\Tests\Discovery\Fixtures\GetOrderHandler;
use SolidFrame\Symfony\Tests\Discovery\Fixtures\GetOrderQuery;
use SolidFrame\Symfony\Tests\Discovery\Fixtures\OrderCreatedEvent;
use SolidFrame\Symfony\Tests\Discovery\Fixtures\SendOrderConfirmationListener;
use SolidFrame\Symfony\Tests\Discovery\Fixtures\UpdateInventoryListener;

final class HandlerDiscoveryTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/Fixtures';
    }

    #[Test]
    public function discoversCommandHandlers(): void
    {
        $handlers = HandlerDiscovery::within([$this->fixturesPath], CommandHandler::class);

        self::assertArrayHasKey(CreateOrderCommand::class, $handlers);
        self::assertSame(CreateOrderHandler::class, $handlers[CreateOrderCommand::class]);
    }

    #[Test]
    public function discoversQueryHandlers(): void
    {
        $handlers = HandlerDiscovery::within([$this->fixturesPath], QueryHandler::class);

        self::assertArrayHasKey(GetOrderQuery::class, $handlers);
        self::assertSame(GetOrderHandler::class, $handlers[GetOrderQuery::class]);
    }

    #[Test]
    public function doesNotDiscoverClassesWithoutMarkerInterface(): void
    {
        $handlers = HandlerDiscovery::within([$this->fixturesPath], CommandHandler::class);

        self::assertCount(1, $handlers);
    }

    #[Test]
    public function discoversEventListeners(): void
    {
        $listeners = HandlerDiscovery::listeners([$this->fixturesPath], EventListener::class);

        self::assertArrayHasKey(OrderCreatedEvent::class, $listeners);
        self::assertCount(2, $listeners[OrderCreatedEvent::class]);
        self::assertContains(SendOrderConfirmationListener::class, $listeners[OrderCreatedEvent::class]);
        self::assertContains(UpdateInventoryListener::class, $listeners[OrderCreatedEvent::class]);
    }

    #[Test]
    public function returnsEmptyArrayForNonExistentDirectory(): void
    {
        self::assertSame([], HandlerDiscovery::within(['/non/existent/path'], CommandHandler::class));
    }

    #[Test]
    public function commandAndQueryHandlersDoNotMix(): void
    {
        $commandHandlers = HandlerDiscovery::within([$this->fixturesPath], CommandHandler::class);
        $queryHandlers = HandlerDiscovery::within([$this->fixturesPath], QueryHandler::class);

        self::assertArrayNotHasKey(GetOrderQuery::class, $commandHandlers);
        self::assertArrayNotHasKey(CreateOrderCommand::class, $queryHandlers);
    }
}
