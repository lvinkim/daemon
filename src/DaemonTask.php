<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2019/2/15
 * Time: 1:24 PM
 */

namespace Lvinkim\Daemon;


use Lvinkim\Daemon\Model\Task;
use Swoole\Coroutine;
use Swoole\Process;

class DaemonTask
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
    private $taskGenerator;

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
     * $taskGenerator 函数必须返回 Task[] 的结构
     *
     * @param callable $taskGenerator
     * @param int $maxProcess
     * @param int $generateInterval
     * @param bool $silent
     */
    public function __construct(callable $taskGenerator, int $maxProcess = 3, int $generateInterval = 60, bool $silent = true)
    {
        $this->taskGenerator = $taskGenerator;
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
        while (1) {
            /** @var Task[] $tasks */
            $tasks = call_user_func($this->taskGenerator);

            foreach ($tasks as $task) {

                if (!($task instanceof Task)) {
                    throw new \ErrorException("task must be Lvinkim\Daemon\Model\Task instance");
                }

                if ($this->isRunning($task)) {
                    continue;
                }
                if ($this->hasFreeProcess()) {
                    $this->startTask($task);
                }
            }

            $coroutineId = $this->wait();

            if ($this->generateInterval < 1) {
                throw new \ErrorException("generateInterval must greater than 0");
            }

            for ($i = 0; $i < $this->generateInterval; $i++) {
                Coroutine::resume($coroutineId);
                sleep(1);
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
                    $this->releaseTaskByPid($pid);
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
     */
    private function releaseTaskByPid(int $pid)
    {
        foreach ($this->runningTasks as $index => $runningTask) {
            if ($runningTask->getPid() == $pid) {
                unset($this->runningTasks[$index]);
            }
        }
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

        $task->setPid($pid);
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
}