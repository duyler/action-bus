<?php

declare(strict_types=1);

namespace Duyler\EventBus\Build;

use Closure;
use Duyler\EventBus\Formatter\IdFormatter;
use UnitEnum;

final readonly class ActionHandlerSubstitution
{
    public string $actionId;

    public function __construct(
        string|UnitEnum $actionId,
        public string|Closure $handler,
        /** @var array<string, string> */
        public array $bind = [],
        /** @var array<string, string> */
        public array $providers = [],
    ) {
        $this->actionId = IdFormatter::toString($actionId);
    }
}
