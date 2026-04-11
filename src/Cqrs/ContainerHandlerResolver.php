<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Cqrs;

use Psr\Container\ContainerInterface;
use SolidFrame\Cqrs\Exception\HandlerNotFoundException;
use SolidFrame\Cqrs\Handler\HandlerResolverInterface;

final readonly class ContainerHandlerResolver implements HandlerResolverInterface
{
    /**
     * @param array<class-string, class-string> $handlers message => handler class mapping
     */
    public function __construct(private ContainerInterface $container, private array $handlers = []) {}

    public function resolve(object $message): callable
    {
        $handlerClass = $this->handlers[$message::class]
            ?? throw HandlerNotFoundException::forMessage($message);

        return $this->container->get($handlerClass);
    }
}
