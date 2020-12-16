<?php 

declare(strict_types=1);

namespace Jine\EventBus;

class Dispatcher extends AbstractDispatcher
{
    public function run(string $startAction): void
    {
        $this->startLoop($startAction);
    }
}
