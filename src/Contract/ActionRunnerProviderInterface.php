<?php

declare(strict_types=1);

namespace Duyler\ActionBus\Contract;

use Closure;
use Duyler\ActionBus\Build\Action;

interface ActionRunnerProviderInterface
{
    public function getRunner(Action $action): Closure;
}
