<?php

declare(strict_types=1);

namespace Duyler\EventBus;

use Duyler\EventBus\Bus\DoWhile;
use Duyler\EventBus\Bus\Rollback;
use Duyler\EventBus\Dto\Result;
use Duyler\EventBus\Service\ResultService;
use Throwable;

readonly class Runner implements BusInterface
{
    public function __construct(
        private DoWhile $doWhile,
        private Rollback $rollback,
        private ResultService $resultService,
    ) {}

    /**
     * @throws Throwable
     */
    public function run(): BusInterface
    {
        try {
            $this->doWhile->run();
        } catch (Throwable $exception) {
            $this->rollback->run();
            throw $exception;
        }

        return $this;
    }

    public function getResult(string $actionId): ?Result
    {
        return $this->resultService->resultIsExists($actionId) ? $this->resultService->getResult($actionId) : null;
    }
}
