<?php

declare(strict_types=1);

namespace Duyler\EventBus;

use Duyler\EventBus\Contract\FinalStateHandlerInterface;
use Duyler\EventBus\Contract\StateHandlerInterface;

class State
{
    /**
     * @var StateHandlerInterface[]
     */
    private array $stateHandlers = [];

    /**
     * @var FinalStateHandlerInterface[]
     */
    private array $finalStateHandlers = [];

    public function __construct(
        private readonly BusControl $busControl,
        private readonly TaskQueue $taskQueue
    ) {
    }

    public function tick(Task $task): void
    {
        if (empty($this->stateHandlers)) {
            return;
        }

        $busControlService = new BusControlService(
            $task->result->status,
            $task->result->data,
            $task->action->id,
            $this->busControl
        );

        foreach ($this->stateHandlers as $handler) {
            $handler->handle($busControlService);
        }

        $this->busControl->resolveSubscribers($task->action->id, $task->result->status);

        if ($this->taskQueue->isEmpty()) {
            foreach ($this->finalStateHandlers as $handler) {
                $handler->handle($busControlService);
            }
        }
    }

    public function addStateHandler(StateHandlerInterface $stateHandler): void
    {
        $this->stateHandlers[] = $stateHandler;
    }

    public function addFinalStateHandler(FinalStateHandlerInterface $finalStateHandler): void
    {
        $this->finalStateHandlers[] = $finalStateHandler;
    }
}
