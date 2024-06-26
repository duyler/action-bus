<?php

declare(strict_types=1);

namespace Duyler\ActionBus\Service;

use Duyler\ActionBus\Bus\Log;
use Duyler\ActionBus\Bus\Rollback;

readonly class RollbackService
{
    public function __construct(private Rollback $rollback, private Log $log) {}

    public function rollbackWithoutException(int $step = 0): void
    {
        $this->rollback->run($step > 0 ? array_slice($this->log->getActionLog(), -1, $step) : []);
    }
}
