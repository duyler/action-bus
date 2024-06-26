<?php

declare(strict_types=1);

namespace Duyler\ActionBus\Exception;

use Exception;

class SubscribedActionNotDefinedException extends Exception
{
    public function __construct(string $actionId)
    {
        parent::__construct('Subscribed action ' . $actionId . ' not defined');
    }
}
