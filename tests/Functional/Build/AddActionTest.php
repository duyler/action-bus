<?php

declare(strict_types=1);

namespace Duyler\EventBus\Test\Functional\Build;

use Duyler\EventBus\Build\Action;
use Duyler\EventBus\BusBuilder;
use Duyler\EventBus\BusConfig;
use Duyler\EventBus\Exception\ActionAlreadyDefinedException;
use Duyler\EventBus\Exception\ActionNotDefinedException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AddActionTest extends TestCase
{
    #[Test]
    public function addAction_with_redefine()
    {
        $builder = new BusBuilder(new BusConfig());

        $builder->addAction(
            new Action(
                id: TestAction::TestAction,
                handler: function () {},
            ),
        );

        $this->expectException(ActionAlreadyDefinedException::class);

        $builder->addAction(
            new Action(
                id: TestAction::TestAction,
                handler: function () {},
            ),
        );
    }

    #[Test]
    public function doAction_with_redefine()
    {
        $builder = new BusBuilder(new BusConfig());

        $builder->doAction(
            new Action(
                id: 'Test',
                handler: function () {},
                externalAccess: true,
            ),
        );

        $this->expectException(ActionAlreadyDefinedException::class);

        $builder->doAction(
            new Action(
                id: 'Test',
                handler: function () {},
                externalAccess: true,
            ),
        );
    }

    #[Test]
    public function doAction_with_underdefine()
    {
        $builder = new BusBuilder(new BusConfig());

        $builder->doAction(
            new Action(
                id: 'Test',
                handler: function () {},
                externalAccess: true,
            ),
        );

        $builder->doAction(
            new Action(
                id: 'TestWithUndefinedRequire',
                handler: function () {},
                required: ['UndefinedRequire'],
                externalAccess: true,
            ),
        );

        $this->expectException(ActionNotDefinedException::class);

        $builder->build()->run();
    }
}

enum TestAction
{
    case TestAction;
}
