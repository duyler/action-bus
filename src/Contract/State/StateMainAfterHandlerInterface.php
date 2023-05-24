<?php

declare(strict_types=1);

namespace Duyler\EventBus\Contract\State;

use Duyler\EventBus\State\Service\StateMainAfterService;
use Duyler\EventBus\State\StateHandlerInterface;

interface StateMainAfterHandlerInterface extends StateHandlerInterface
{
    public function handle(StateMainAfterService $stateService): void;
}
