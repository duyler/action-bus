<?php

declare(strict_types=1);

namespace Duyler\ActionBus;

use Duyler\ActionBus\Dto\Log;
use Duyler\ActionBus\Dto\Result;
use Duyler\ActionBus\Dto\Event;
use Throwable;
use UnitEnum;

interface BusInterface
{
    /**
     * @throws Throwable
     */
    public function run(): BusInterface;

    public function getResult(string|UnitEnum $actionId): Result;

    public function resultIsExists(string|UnitEnum $actionId): bool;

    public function dispatchEvent(Event $event): BusInterface;

    public function reset(): BusInterface;

    public function getLog(): Log;
}
