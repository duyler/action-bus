<?php

declare(strict_types=1);

namespace Duyler\ActionBus\Test\Functional\Run;

use Duyler\ActionBus\Build\Action;
use Duyler\ActionBus\BusBuilder;
use Duyler\ActionBus\BusConfig;
use Duyler\ActionBus\Enum\ResultStatus;
use Duyler\ActionBus\Exception\NotAllowedSealedActionException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SealedActionTest extends TestCase
{
    #[Test]
    public function run_require_accept_action(): void
    {
        $builder = new BusBuilder(new BusConfig());
        $builder->addAction(
            new Action(
                id: 'SealedAction',
                handler: function () {},
                externalAccess: true,
                sealed: ['AcceptAction'],
            ),
        );

        $builder->doAction(
            new Action(
                id: 'AcceptAction',
                handler: function () {},
                required: ['SealedAction'],
                externalAccess: true,
            ),
        );

        $bus = $builder->build();
        $bus->run();

        $result = $bus->getResult('SealedAction');
        $this->assertEquals(ResultStatus::Success, $result->status);
    }

    #[Test]
    public function run_require_not_accept_action(): void
    {
        $builder = new BusBuilder(new BusConfig());
        $builder->addAction(
            new Action(
                id: 'SealedAction',
                handler: function () {},
                externalAccess: true,
                sealed: ['AcceptAction'],
            ),
        );

        $builder->doAction(
            new Action(
                id: 'NotAcceptAction',
                handler: function () {},
                required: ['SealedAction'],
                externalAccess: true,
            ),
        );

        $builder->doAction(
            new Action(
                id: 'SomeAction',
                handler: function () {},
                externalAccess: true,
            ),
        );

        $this->expectException(NotAllowedSealedActionException::class);

        $builder->build();
    }
}
