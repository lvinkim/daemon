<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2019/3/2
 * Time: 11:16 PM
 */

namespace Lvinkim\Daemon;


use Lvinkim\Daemon\Model\Task;
use Swoole\Coroutine;
use Swoole\Process;

class TaskLimiter
{
    /**
     * 正在运行的进程列表
     * @var Task[]
     */
    private $runningTasks = [];

    /**
     * 生成任务的函数
     * @var callable
     */
    private $taskBuilder;

    /**
     * 最大进程数
     * @var int
     */
    private $maxProcess;

    /**
     * 生成函数的执行间隔
     * @var int
     */
    private $generateInterval;

    /**
     * 沉默模式
     * @var bool
     */
    private $silent;

    /**
     * DaemonTask constructor.
     *
     * $taskBuilder 函数必须返回 Task 的结构
     *
     * @param callable $taskBuilder
     * @param int $maxProcess
     * @param int $generateInterval
     * @param bool $silent
     */
    public function __construct(callable $taskBuilder, int $maxProcess = 3, int $generateInterval = 60, bool $silent = true)
    {
        $this->taskBuilder = $taskBuilder;
        $this->maxProcess = $maxProcess;
        $this->generateInterval = $generateInterval;
        $this->silent = $silent;
    }

    /**
     * 运行守护
     * @throws \ErrorException
     */
    public function run()
    {
        $coroutineId = $this->wait();

        while (1) {

            if ($this->hasFreeProcess()) {
                /** @var Task $tasks */
                $task = call_user_func($this->taskBuilder);

                if (!($task instanceof Task)) {
                    printf("task must be Lvinkim\Daemon\Model\Task instance\n");
                } else {
                    if ($this->isRunning($task)) {
                        if (!$this->silent) {
                            printf("任务: %s, 正在运行于进程: %s\n", $task->getId(), $this->getRunningPid($task));
                        }

                    } else {
                        $this->startTask($task);
                    }
                }

                usleep(200000);
                Coroutine::resume($coroutineId);

            } else {
                if ($this->generateInterval < 1) {
                    throw new \ErrorException("generateInterval must greater than 0");
                }

                printf("当前已无可用进程配额（已用 %s, 总共 %s），休息 %s 秒\n",
                    count($this->runningTasks),
                    $this->maxProcess,
                    $this->generateInterval
                );

                for ($i = 0; $i < $this->generateInterval; $i++) {
                    Coroutine::resume($coroutineId);
                    usleep(100000);
                }
            }
        }
    }

    /**
     * 收回进程资源，并空出位置
     * @return mixed
     */
    private function wait()
    {
        $coroutineId = go(function () {
            while (1) {
                if ($ret = Process::wait(false)) {
                    $pid = intval($ret["pid"] ?? 0);
                    $succeed = $this->releaseTaskByPid($pid);

                    if (!$this->silent) {
                        printf("回收进程: %s, 结果 %s\n", $pid, intval($succeed));
                    }

                } else {
                    Coroutine::yield();
                }
            }
        });

        return $coroutineId;
    }

    /**
     * 释放已退出的进程
     * @param int $pid
     * @return bool
     */
    private function releaseTaskByPid(int $pid): bool
    {
        $succeed = false;
        foreach ($this->runningTasks as $index => $runningTask) {
            if ($runningTask->getPid() == $pid) {
                unset($this->runningTasks[$index]);
                $succeed = true;
            }
        }
        $this->runningTasks = array_values($this->runningTasks);

        return $succeed;
    }

    /**
     * 拉起进程
     * @param Task $task
     * @return int
     */
    private function startTask(Task $task): int
    {
        $command = $task->getCommand();
        $process = new Process(function (Process $worker) use ($command) {
            $worker->exec('/bin/sh', ['-c', $command]);
        });
        $pid = $process->start();

        $task->setPid(strval($pid));
        $this->runningTasks[] = $task;

        if (!$this->silent) {
            printf("[%s] 执行任务ID %s , 进程号 %s, 命令行 '%s', \n",
                date("Y-m-d H:i:s"),
                $task->getId(), $task->getPid(), $task->getCommand());
            printf("当前运行中的进程数: %s, 总可用进程数 %s\n", count($this->runningTasks), $this->maxProcess);
        }

        return $pid;
    }


    /**
     * 是否还有空闲进程位置
     * @return bool
     */
    private function hasFreeProcess(): bool
    {
        if (count($this->runningTasks) < $this->maxProcess) {
            return true;
        }
        return false;
    }

    /**
     * 判断任务是否运行中
     * @param Task $task
     * @return bool
     */
    private function isRunning(Task $task): bool
    {
        foreach ($this->runningTasks as $runningTask) {
            if ($runningTask->getId() === $task->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Task $task
     * @return string
     */
    private function getRunningPid(Task $task): string
    {
        $runningPid = "";
        foreach ($this->runningTasks as $runningTask) {
            if ($runningTask->getId() === $task->getId()) {
                $runningPid = $runningTask->getPid();
            }
        }
        return $runningPid;
    }
}