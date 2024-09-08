<?php

declare(strict_types=1);

namespace Duyler\EventBus;

use Duyler\EventBus\Build\Action;
use Duyler\EventBus\Build\Context;
use Duyler\EventBus\Build\Event;
use Duyler\EventBus\Build\SharedService;
use Duyler\EventBus\Build\Subscription;
use Duyler\EventBus\Contract\State\StateHandlerInterface;
use Duyler\EventBus\Exception\ActionAlreadyDefinedException;
use Duyler\EventBus\Exception\SubscriptionAlreadyDefinedException;
use Duyler\EventBus\Internal\ListenerProvider;
use Duyler\EventBus\Service\ActionService;
use Duyler\EventBus\Service\EventService;
use Duyler\EventBus\Service\StateService;
use Duyler\EventBus\Service\SubscriptionService;
use Duyler\DependencyInjection\Container;
use Duyler\DependencyInjection\ContainerConfig;
use Psr\EventDispatcher\ListenerProviderInterface;

class BusBuilder
{
    /** @var array<string, Action> */
    private array $actions = [];

    /** @var Subscription[] */
    private array $subscriptions = [];

    /** @var array<string, Action> */
    private array $doActions = [];

    /** @var StateHandlerInterface[] */
    private array $stateHandlers = [];

    /** @var SharedService[] */
    private array $sharedServices = [];

    /** @var array<string, string> */
    private array $bind = [];

    /** @var Context[] */
    private array $contexts = [];

    /** @var array<string, Event> */
    private array $events = [];

    public function __construct(private BusConfig $config) {}

    /**
     * @psalm-suppress MoreSpecificReturnType, LessSpecificReturnStatement
     */
    public function build(): BusInterface
    {
        $containerConfig = new ContainerConfig();
        $containerConfig->withBind($this->config->bind);
        $containerConfig->withProvider($this->config->providers);

        foreach ($this->config->definitions as $definition) {
            $containerConfig->withDefinition($definition);
        }

        $container = new Container($containerConfig);
        $container->set($this->config);
        $container->bind($this->config->bind);

        /** @var ActionService $actionService */
        $actionService = $container->get(ActionService::class);

        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        /** @var SubscriptionService $subscriptionService */
        $subscriptionService = $container->get(SubscriptionService::class);

        /** @var StateService $stateService */
        $stateService = $container->get(StateService::class);

        $eventService->collect($this->events);

        foreach ($this->sharedServices as $sharedService) {
            $actionService->addSharedService($sharedService);
        }

        $actionService->collect($this->actions);

        foreach ($this->doActions as $action) {
            $actionService->doExistsAction($action->id);
        }

        foreach ($this->subscriptions as $subscription) {
            $subscriptionService->addSubscription($subscription);
        }

        foreach ($this->stateHandlers as $stateHandler) {
            $stateService->addStateHandler($stateHandler);
        }

        foreach ($this->contexts as $context) {
            $stateService->addStateContext($context);
        }

        $termination = new Termination($container);
        $container->set($termination);

        /** @var ListenerProvider $listenerProvider */
        $listenerProvider = $container->get(ListenerProviderInterface::class);

        foreach ($this->config->getListeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                $listenerProvider->addListener($event, $container->get($listener));
            }
        }

        return $container->get(Bus::class);
    }

    public function addAction(Action $action): static
    {
        if (array_key_exists($action->id, $this->actions)) {
            throw new ActionAlreadyDefinedException($action->id);
        }

        $this->actions[$action->id] = $action;

        return $this;
    }

    public function addSubscription(Subscription $subscription): static
    {
        $id = $subscription->subjectId . '@' . $subscription->status->value . '@' . $subscription->actionId;

        if (array_key_exists($id, $this->subscriptions)) {
            throw new SubscriptionAlreadyDefinedException($subscription);
        }

        $this->subscriptions[$id] = $subscription;

        return $this;
    }

    public function doAction(Action $action): static
    {
        if (array_key_exists($action->id, $this->actions)) {
            throw new ActionAlreadyDefinedException($action->id);
        }

        $this->actions[$action->id] = $action;
        $this->doActions[$action->id] = $action;

        return $this;
    }

    public function addStateHandler(StateHandlerInterface $stateHandler): static
    {
        $this->stateHandlers[get_class($stateHandler)] = $stateHandler;

        return $this;
    }

    public function addStateContext(Context $context): static
    {
        $this->contexts[] = $context;

        return $this;
    }

    public function addSharedService(SharedService $sharedService): static
    {
        $this->sharedServices[] = $sharedService;

        return $this;
    }

    public function addEvent(Event $event): static
    {
        $this->events[$event->id] = $event;

        return $this;
    }
}
