<?php

declare(strict_types=1);

namespace Duyler\EventBus\Action;

use Closure;
use Duyler\EventBus\Action\Exception\ActionHandlerMustBeCallableException;
use Duyler\EventBus\Build\Action;
use Duyler\EventBus\Bus\ActionContainer;

class ActionHandlerBuilder
{
    public function __construct(
        private ActionSubstitution $actionSubstitution,
    ) {}

    public function build(Action $action, ActionContainer $container): callable
    {
        if ($this->actionSubstitution->isSubstituteHandler($action->id)) {
            $handlerSubstitution = $this->actionSubstitution->getSubstituteHandler($action->id);
            if ($handlerSubstitution->handler instanceof Closure) {
                return $handlerSubstitution->handler;
            }
            $container->addProviders($handlerSubstitution->providers);
            $container->bind($handlerSubstitution->bind);
            return $container->get($handlerSubstitution->handler);
        }

        if ($action->handler instanceof Closure) {
            return $action->handler;
        }

        $handler = $container->get($action->handler);

        if (!is_callable($handler)) {
            throw new ActionHandlerMustBeCallableException();
        }

        return $handler;
    }
}
