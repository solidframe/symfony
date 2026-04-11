<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\EventDriven;

use Psr\Container\ContainerInterface;
use SolidFrame\EventDriven\Listener\ListenerResolverInterface;

final readonly class ContainerListenerResolver implements ListenerResolverInterface
{
    /**
     * @param array<class-string, list<class-string>> $listeners event => listener classes mapping
     */
    public function __construct(private ContainerInterface $container, private array $listeners = []) {}

    /** @return list<callable> */
    public function resolve(object $event): array
    {
        $listenerClasses = $this->listeners[$event::class] ?? [];

        return array_map(
            $this->container->get(...),
            $listenerClasses,
        );
    }
}
