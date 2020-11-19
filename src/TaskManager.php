<?php 

declare(strict_types=1);

namespace Jine\EventBus;

use Jine\EventBus\Dto\Task;
use Jine\EventBus\Contract\HandlerInterface;

class TaskManager
{
    private ConfigProvider $config;
    private Rollback $rollback;
    private ResultStorage $resultStorage;
    private TaskStorage $taskStorage;
    private array $containers = [];
    
    public function __construct(
        ConfigProvider $config,
        Rollback $rollback,
        ResultStorage $resultStorage,
        TaskStorage $taskStorage
    ) {
        $this->config = $config;
        $this->rollback = $rollback;
        $this->resultStorage = $resultStorage;
        $this->taskStorage = $taskStorage;
    }
    
    // Принимает задачу на обработку
    public function handle(Task $task, \Closure $callback): void
    {
        $handler = new $task->handler;
        $this->run($handler, $task, $callback);
    }
    
    // Передает задачу в обработчик // Здесь происходит ожидание результата
    private function run(HandlerInterface $handler, Task $task, \Closure $callback)
    {
        $container = new Container();

        $data = $this->resultStorage->getAllByArray($task->required);

        foreach ($data as $value) {
            $container->set($value);
        }

        $classMap = $handler->getClassMap();

        if (empty($classMap) === false) {
            $container->setClassMap($classMap);
        }

        $service = $container->instance($handler->getClass());

        $this->containers[$task->serviceId] = $container;

        try {
            $result = $handler->run($service);

            if ($result !== null) {
                $this->resultStorage->save($task->serviceId . '.' . $task->action, $result);
            }
            $callback($result);
        } catch(\Throwable $exception) {
            $this->rollback($task, $exception);
            throw $exception;
        }
    }

    public function rollback(Task $task, \Throwable $exception): void
    {
        $this->taskStorage->save($task);

        $this->rollback->run($this->taskStorage->getAll());
    }
}
