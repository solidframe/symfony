<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Tests\Discovery\Fixtures;

use SolidFrame\Cqrs\QueryHandler;

final class GetOrderHandler implements QueryHandler
{
    public function __invoke(GetOrderQuery $query): mixed
    {
        return null;
    }
}
