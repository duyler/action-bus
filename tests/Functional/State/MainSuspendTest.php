<?php

declare(strict_types=1);

namespace Duyler\ActionBus\Test\Functional\State;

use Duyler\ActionBus\BusBuilder;
use Duyler\ActionBus\BusConfig;
use Duyler\ActionBus\Contract\State\MainResumeStateHandlerInterface;
use Duyler\ActionBus\Contract\State\MainSuspendStateHandlerInterface;
use Duyler\ActionBus\Dto\Action;
use Duyler\ActionBus\Dto\Context;
use Duyler\ActionBus\State\Service\StateMainResumeService;
use Duyler\ActionBus\State\Service\StateMainSuspendService;
use Duyler\ActionBus\State\StateContext;
use Duyler\ActionBus\State\Suspend;
use Fiber;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

class MainSuspendTest extends TestCase
{
    #[Test]
    public function suspend_without_handlers()
    {
        $busBuilder = new BusBuilder(new BusConfig());

        $busBuilder->doAction(
            new Action(
                id: 'TestSuspend',
                handler: function () {
                    $data = new stdClass();
                    $data->hello = Fiber::suspend(fn() => 'Hello') . ', World!';

                    return $data;
                },
                contract: stdClass::class,
                externalAccess: true,
            ),
        );

        $bus = $busBuilder->build();
        $bus->run();

        $this->assertEquals('Hello, World!', $bus->getResult('TestSuspend')->data->hello);
    }

    #[Test]
    public function suspend_with_callback()
    {
        $busBuilder = new BusBuilder(new BusConfig());
        $busBuilder->addStateHandler(new MainSuspendStateHandler());
        $busBuilder->addStateHandler(new MainResumeStateHandler());
        $busBuilder->addStateContext(new Context(
            [
                MainSuspendStateHandler::class,
                MainResumeStateHandler::class,
            ],
        ));

        $busBuilder->doAction(
            new Action(
                id: 'TestSuspend1',
                handler: function () {
                    $callback = Fiber::suspend(fn() => 'Hello');
                    $result = $callback();
                    $data = new stdClass();
                    $data->hello = $result;

                    return $data;
                },
                contract: stdClass::class,
                externalAccess: true,
            ),
        );

        $busBuilder->doAction(
            new Action(
                id: 'TestSuspend2',
                handler: function () {
                    $callback = Fiber::suspend(fn() => 'Hello');
                    $result = $callback();
                    $data = new stdClass();
                    $data->hello = $result;

                    return $data;
                },
                required: ['TestSuspend1'],
                contract: stdClass::class,
                externalAccess: true,
            ),
        );

        $bus = $busBuilder->build();
        $bus->run();

        $this->assertTrue($bus->resultIsExists('TestSuspend1'));
        $this->assertEquals('Hello, World!', $bus->getResult('TestSuspend1')->data->hello);
        $this->assertTrue($bus->resultIsExists('TestSuspend2'));
        $this->assertEquals('Hello, World!', $bus->getResult('TestSuspend2')->data->hello);
    }
}

class MainSuspendStateHandler implements MainSuspendStateHandlerInterface
{
    #[Override]
    public function handle(StateMainSuspendService $stateService, StateContext $context): mixed
    {
        if ($stateService->getActionId() === 'TestSuspend1') {
            $stateService->getContainer();
        }

        /** @var callable $value */
        $value = $stateService->getValue();

        $result = $value();

        return fn() => $result . ', World!';
    }

    #[Override]
    public function isResumable(Suspend $suspend, StateContext $context): bool
    {
        return true;
    }
}

class MainResumeStateHandler implements MainResumeStateHandlerInterface
{
    #[Override]
    public function handle(StateMainResumeService $stateService, StateContext $context): mixed
    {
        $stateService->getActionId();
        if ($stateService->resultIsExists('TestSuspend2')) {
            $stateService->getResult('TestSuspend2');
        }
        return $stateService->getResumeValue();
    }
}
