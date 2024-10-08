<?php

declare(strict_types=1);

namespace Duyler\EventBus;

use Duyler\DI\ContainerInterface;
use Duyler\EventBus\Bus\Log;
use Duyler\EventBus\Dto\Log as LogDto;

class Termination
{
    private Log $log;
    private ?LogDto $logDto = null;

    public function __construct(
        private ContainerInterface $container,
    ) {
        /** @var Log */
        $this->log = $this->container->get(Log::class);
    }

    public function run(): void
    {
        $this->logDto = $this->log->getLog();
        $this->container->finalize();
    }

    public function getLog(): LogDto
    {
        if (null === $this->logDto) {
            $this->logDto = $this->log->getLog();
        }

        return $this->logDto;
    }
}
