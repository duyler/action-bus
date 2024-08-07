<?php

declare(strict_types=1);

namespace Duyler\ActionBus\Service;

use Duyler\ActionBus\Action\ActionContainerProvider;
use Duyler\ActionBus\Build\Action;
use Duyler\ActionBus\Build\ActionHandlerSubstitution;
use Duyler\ActionBus\Build\ActionResultSubstitution;
use Duyler\ActionBus\Build\SharedService;
use Duyler\ActionBus\Bus\ActionRequiredIterator;
use Duyler\ActionBus\Bus\Bus;
use Duyler\ActionBus\Contract\ActionSubstitutionInterface;
use Duyler\ActionBus\Exception\ActionAlreadyDefinedException;
use Duyler\ActionBus\Exception\ActionNotDefinedException;
use Duyler\ActionBus\Exception\CannotRequirePrivateActionException;
use Duyler\ActionBus\Exception\EventNotDefinedException;
use Duyler\ActionBus\Exception\NotAllowedSealedActionException;
use Duyler\ActionBus\Storage\ActionStorage;
use Duyler\ActionBus\Storage\EventStorage;
use Duyler\ActionBus\Storage\SubscriptionStorage;

readonly class ActionService
{
    public function __construct(
        private ActionStorage $actionStorage,
        private ActionContainerProvider $actionContainerProvider,
        private ActionSubstitutionInterface $actionSubstitution,
        private SubscriptionStorage $subscriptionStorage,
        private EventStorage $eventStorage,
        private Bus $bus,
    ) {}

    public function addAction(Action $action): void
    {
        if ($this->actionStorage->isExists($action->id)) {
            throw new ActionAlreadyDefinedException($action->id);
        }

        /** @var string $subject */
        foreach ($action->required as $subject) {
            if (false === $this->actionStorage->isExists($subject)) {
                $this->throwActionNotDefined($subject);
            }

            $requiredAction = $this->actionStorage->get($subject);

            $this->checkRequiredAction($action->id, $requiredAction);
        }

        foreach ($action->alternates as $actionId) {
            if (false === $this->actionStorage->isExists($actionId)) {
                $this->throwActionNotDefined($actionId);
            }
        }

        if (null !== $action->listen) {
            if (false === $this->eventStorage->has($action->listen)) {
                $this->throwEventNotDefined($action->listen, $action->id);
            }
        }

        $this->actionStorage->save($action);
    }

    public function doAction(Action $action): void
    {
        $this->addAction($action);

        $this->bus->doAction($action);
    }

    public function doExistsAction(string $actionId): void
    {
        if (false === $this->actionStorage->isExists($actionId)) {
            $this->throwActionNotDefined($actionId);
        }

        $action = $this->actionStorage->get($actionId);

        $this->bus->doAction($action);
    }

    public function getById(string $actionId): Action
    {
        if (false === $this->actionStorage->isExists($actionId)) {
            $this->throwActionNotDefined($actionId);
        }

        return $this->actionStorage->get($actionId);
    }

    /** @return array<string, Action> */
    public function getByContract(string $contract): array
    {
        return $this->actionStorage->getByContract($contract);
    }

    public function actionIsExists(string $actionId): bool
    {
        return $this->actionStorage->isExists($actionId);
    }

    /**
     * @param array<string, Action> $actions
     */
    public function collect(array $actions): void
    {
        foreach ($actions as $action) {
            $requiredIterator = new ActionRequiredIterator($action->required, $actions);

            /** @var string $subject */
            foreach ($requiredIterator as $subject) {
                if (false === array_key_exists($subject, $actions)) {
                    $this->throwActionNotDefined($subject);
                }

                $this->checkRequiredAction($action->id, $actions[$subject]);
            }

            foreach ($action->alternates as $actionId) {
                if (false === array_key_exists($actionId, $actions)) {
                    $this->throwActionNotDefined($actionId);
                }
            }

            if (null !== $action->listen) {
                if (false === $this->eventStorage->has($action->listen)) {
                    $this->throwEventNotDefined($action->listen, $action->id);
                }
            }

            $this->actionContainerProvider->buildContainer($action);

            $this->actionStorage->save($action);
        }
    }

    private function checkRequiredAction(string $subject, Action $requiredAction): void
    {
        if ($requiredAction->private) {
            throw new CannotRequirePrivateActionException($subject, $requiredAction->id);
        }

        if (count($requiredAction->sealed) > 0 && !in_array($subject, $requiredAction->sealed)) {
            throw new NotAllowedSealedActionException($subject, $requiredAction->id);
        }
    }

    private function throwActionNotDefined(string $subject): never
    {
        throw new ActionNotDefinedException($subject);
    }

    private function throwEventNotDefined(string $eventId, string $actionId): never
    {
        throw new EventNotDefinedException($eventId, $actionId);
    }

    public function addSharedService(SharedService $sharedService): void
    {
        $this->actionContainerProvider->addSharedService($sharedService);
    }

    public function addResultSubstitutions(ActionResultSubstitution $actionResultSubstitution): void
    {
        $this->actionSubstitution->addResultSubstitutions($actionResultSubstitution);
    }

    public function addHandlerSubstitution(ActionHandlerSubstitution $handlerSubstitution): void
    {
        $this->actionSubstitution->addHandlerSubstitution($handlerSubstitution);
    }

    public function removeAction(string $actionId): void
    {
        $actions = $this->actionStorage->getAll();

        foreach ($actions as $action) {
            if (in_array($actionId, $action->alternates) || in_array($actionId, $action->required->getArrayCopy())) {
                $this->removeAction($action->id);
            }
        }

        $this->actionStorage->remove($actionId);
        $this->subscriptionStorage->removeByActionId($actionId);
    }
}
