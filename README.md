# SolidFrame Symfony

Symfony bridge for SolidFrame architectural packages.

Bundle, compiler passes, console generators, DBAL stores, and modular monolith support — all wired into Symfony.

## Installation

```bash
composer require solidframe/symfony
```

Register the bundle:

```php
// config/bundles.php
return [
    // ...
    SolidFrame\Symfony\SolidFrameBundle::class => ['all' => true],
];
```

## Features at a Glance

| Feature | What You Get |
|---|---|
| **CQRS** | CommandBus, QueryBus, handler auto-discovery via compiler pass |
| **Event-Driven** | EventBus, listener auto-discovery, multi-listener |
| **Event Sourcing** | DBAL EventStore, SnapshotStore, schema SQL |
| **Saga** | DBAL SagaStore, `solidframe:saga:status` |
| **Modular** | Module auto-discovery, registry, `solidframe:module:list` |
| **Generators** | 10 `make:*` commands for DDD, CQRS, events, sagas, modules |

## Handler Auto-Discovery

SolidFrame discovers your handlers at compile time via `HandlerDiscoveryCompilerPass`.

```php
// src/Application/Handler/PlaceOrderHandler.php
final readonly class PlaceOrderHandler implements CommandHandler
{
    public function __invoke(PlaceOrder $command): void { /* ... */ }
}

// No service tags needed. Inject and use:
$commandBus->dispatch(new PlaceOrder('order-123', 'customer-456'));
```

Handlers are discovered by marker interfaces (`CommandHandler`, `QueryHandler`, `EventListener`) and their `__invoke()` type hints.

## Configuration

```yaml
# config/packages/solid_frame.yaml
solid_frame:
    discovery:
        enabled: true
        paths: ['src']

    cqrs:
        command_bus:
            middleware: []
        query_bus:
            middleware: []

    event_driven:
        event_bus:
            middleware: []

    event_sourcing:
        event_store:
            driver: dbal          # 'dbal' or 'in_memory'
            connection: null       # null = default DBAL connection
            table: event_store
        snapshot_store:
            driver: dbal
            connection: null
            table: snapshots

    saga:
        store:
            driver: dbal
            connection: null
            table: sagas

    modular:
        path: modules
        auto_discovery: true
```

## Console Commands

### Generators

```bash
# DDD
php bin/console make:entity Order
php bin/console make:value-object Email
php bin/console make:aggregate-root Order

# CQRS
php bin/console make:cqrs-command PlaceOrder --handler
php bin/console make:command-handler PlaceOrderHandler --command-class=PlaceOrder
php bin/console make:query GetOrderById --handler
php bin/console make:query-handler GetOrderByIdHandler --query-class=GetOrderById

# Event-Driven
php bin/console make:domain-event OrderPlaced --listener
php bin/console make:event-listener SendOrderConfirmation --event-class=OrderPlaced

# Saga
php bin/console make:saga PlaceOrderSaga

# Module
php bin/console make:module Order
```

All generators support subdirectories: `php bin/console make:entity Order/OrderItem`

### Operational

```bash
# List registered modules
php bin/console solidframe:module:list

# View saga details
php bin/console solidframe:saga:status {saga-id}
```

## Database Schema

SQL schema files for DBAL stores are included in the package. Create the tables manually or via your migration system:

**Event Store** (`event_store`):
- `aggregate_id`, `version`, `event_type`, `payload` (JSON), `occurred_at`
- Unique constraint on `(aggregate_id, version)` for optimistic concurrency

**Snapshots** (`snapshots`):
- `aggregate_id`, `aggregate_type`, `version`, `state` (JSON)

**Sagas** (`sagas`):
- `id`, `saga_type`, `status`, `associations` (JSON), `state` (serialized)

## Modular Monolith

### Create a Module

```bash
php bin/console make:module Inventory
```

### Module Definition

```php
use SolidFrame\Modular\Module\AbstractModule;

final class InventoryModule extends AbstractModule
{
    public function __construct()
    {
        parent::__construct(
            name: 'inventory',
            dependsOn: ['catalog'],
        );
    }
}
```

When `solid_frame.modular.auto_discovery` is `true`, modules are discovered from `*Module.php` files in the configured path and registered automatically.

```bash
php bin/console solidframe:module:list
```

## Middleware

Add middleware to buses via config:

```yaml
solid_frame:
    cqrs:
        command_bus:
            middleware:
                - App\Middleware\TransactionMiddleware
                - App\Middleware\LoggingMiddleware
```

Middleware classes are resolved from the service container.

## DI Services

The bundle registers these services:

| Interface | Implementation |
|---|---|
| `CommandBusInterface` | `CommandBus` with discovered handlers |
| `QueryBusInterface` | `QueryBus` with discovered handlers |
| `EventBusInterface` | `EventBus` with discovered listeners |
| `EventStoreInterface` | `DbalEventStore` or `InMemoryEventStore` |
| `SnapshotStoreInterface` | `DbalSnapshotStore` or `InMemorySnapshotStore` |
| `SagaStoreInterface` | `DbalSagaStore` or `InMemorySagaStore` |
| `ModuleRegistryInterface` | `InMemoryModuleRegistry` |

All services are public for container access. Store driver falls back to in-memory when DBAL is not installed.

## Requirements

- PHP 8.2+
- Symfony 6.4 or 7.x

Optional packages (installed as needed):
- `solidframe/ddd` — for `make:entity`, `make:value-object`, `make:aggregate-root`
- `solidframe/cqrs` — for CommandBus, QueryBus, handler discovery
- `solidframe/event-driven` — for EventBus, listener discovery
- `solidframe/event-sourcing` — for EventStore, SnapshotStore
- `solidframe/modular` — for module support
- `solidframe/saga` — for SagaStore
- `doctrine/dbal` — for database-backed stores

## Related Packages

- [solidframe/core](../core) — Bus interfaces, Middleware
- [solidframe/ddd](../ddd) — Entity, ValueObject, AggregateRoot
- [solidframe/cqrs](../cqrs) — CommandBus, QueryBus
- [solidframe/event-driven](../event-driven) — EventBus, Listeners
- [solidframe/event-sourcing](../event-sourcing) — EventStore, Snapshots
- [solidframe/modular](../modular) — Module contracts
- [solidframe/saga](../saga) — Saga lifecycle
- [solidframe/laravel](../laravel) — Laravel alternative
