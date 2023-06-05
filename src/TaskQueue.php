<?php

declare(strict_types=1);

namespace Duyler\EventBus;

use RuntimeException;
use SplQueue;

class TaskQueue
{
    private SplQueue $queue;

    public function __construct(SplQueue $splQueue)
    {
        $this->queue = $splQueue;
        $this->queue->setIteratorMode(SplQueue::IT_MODE_DELETE);
    }

    public function push(Task $task): void
    {
        $this->queue->push($task);
    }

    public function isNotEmpty(): bool
    {
        return $this->queue->isEmpty() === false;
    }

    public function isEmpty(): bool
    {
        return $this->queue->isEmpty();
    }

    public function dequeue(): Task
    {
        if ($this->queue->isEmpty()) {
            throw new RuntimeException("TaskQueue is empty");
        }

        return $this->queue->dequeue();
    }
}
