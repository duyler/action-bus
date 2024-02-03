<?php

declare(strict_types=1);

namespace Duyler\EventBus\Contract;

use Duyler\EventBus\Dto\Action;
use Duyler\EventBus\Dto\Result;

interface ActionRunnerInterface
{
    public function run(Action $action): Result;
}
