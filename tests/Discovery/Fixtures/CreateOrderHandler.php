<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Tests\Discovery\Fixtures;

use SolidFrame\Cqrs\CommandHandler;

final class CreateOrderHandler implements CommandHandler
{
    public function __invoke(CreateOrderCommand $command): void {}
}
