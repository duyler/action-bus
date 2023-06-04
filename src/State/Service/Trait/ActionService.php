<?php

declare(strict_types=1);

namespace Duyler\EventBus\State\Service\Trait;

use Duyler\EventBus\BusService;
use Duyler\EventBus\Dto\Action;
use Duyler\EventBus\Dto\Subscription;

/**
 * @property \Duyler\EventBus\Service\ActionService $actionService
 */
trait ActionService
{
    public function addAction(Action $action): void
    {
        if ($this->actionService->actionIsExists($action->id) === false) {
            $this->actionService->addAction($action);
        }
    }

    public function doAction(Action $action): void
    {
        $this->actionService->doAction($action);
    }

    public function doExistsAction(string $actionId): void
    {
        $this->actionService->doExistsAction($actionId);
    }

    public function actionIsExists(string $actionId): bool
    {
        return $this->actionService->actionIsExists($actionId);
    }
}
