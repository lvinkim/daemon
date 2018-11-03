<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 02/11/2018
 * Time: 10:47 PM
 */

namespace Lvinkim\Daemon;


use Lvinkim\Daemon\Model\WorkingProcess;
use Lvinkim\Daemon\Model\Worker;
use Swoole\Coroutine;
use Swoole\Process;

/**
 * 根据配置文件 守护多个使用 shell 命令行调用启动的进程
 * Class DaemonMany
 * @package Lvinkim\Daemon
 */
class DaemonMany
{
    /** @var WorkingProcess[] */
    private $workingProcesses = [];

    /** @var Worker[] */
    private $workers = [];

    /** @var string */
    private $configPath;

    /** @var bool */
    private $autoReload;

    public function __construct(string $configPath, bool $autoReload = false)
    {
        $this->configPath = $configPath;
        $this->autoReload = $autoReload;
    }

    public function run()
    {
        $this->loadWorkers();

        pcntl_signal(SIGHUP, function () {
            $this->loadWorkers();
        });

        $waitCoroutineId = $this->wait();

        $reloadCoroutineId = $this->reload();

        go(function () use ($waitCoroutineId, $reloadCoroutineId) {
            while (1) {
                pcntl_signal_dispatch();
                Coroutine::resume($waitCoroutineId);
                Coroutine::resume($reloadCoroutineId);
                sleep(1);
            }
        });
    }

    /**
     * 等待 worker
     * @return mixed
     */
    private function wait()
    {
        $coroutineId = go(function () {
            while (1) {
                if ($ret = Process::wait(false)) {
                    $index = $this->getIndexOfWorkingProcessesByPid($ret["pid"]);

                    if (false !== $index) {
                        if ($this->workingProcesses[$index]->isStopping()) {
                            printf("[%s] 移除守护 %s\n", date("Y-m-d H:i:s"), $this->workingProcesses[$index]->getWorker()->getId());

                            unset($this->workingProcesses[$index]);
                        } else {
                            $command = $this->workingProcesses[$index]->getWorker()->getCommand();
                            $newPid = $this->createWorker($command);
                            $this->workingProcesses[$index]->setPid($newPid);

                            printf("[%s] 重新拉起 %s\n", date("Y-m-d H:i:s"), $this->workingProcesses[$index]->getWorker()->getId());
                        }
                    }

                } else {
                    Coroutine::yield();
                }
            }
        });

        return $coroutineId;
    }

    private function reload()
    {
        $reloadCoroutineId = go(function () {
            while (1) {
                if ($this->autoReload) {
                    $second = intval(date('s'));
                    // 每 15 秒自动重载一次配置
                    if ($second % 15 == 0) {
                        printf("[%s] 自动重载配置 %s\n", date("Y-m-d H:i:s"), $this->configPath);
                        $this->loadWorkers();
                    }
                }
                Coroutine::yield();
            }
        });

        return $reloadCoroutineId;
    }

    /**
     * 加载 workers
     */
    private function loadWorkers()
    {
        $this->parseConfig();
        foreach ($this->workers as $worker) {
            if ($worker->isEnabled()) {
                printf("[%s] 启用 %s\n", date("Y-m-d H:i:s"), $worker->getId());
                $this->startWorker($worker);
            } else {
                printf("[%s] 停用 %s\n", date("Y-m-d H:i:s"), $worker->getId());
                $this->stopWorker($worker);
            }
        }
    }

    /**
     * 启动 worker
     * @param Worker $worker
     */
    private function startWorker(Worker $worker)
    {
        $index = $this->getIndexOfWorkingProcesses($worker->getId());
        if (false === $index) {
            $pid = $this->createWorker($worker->getCommand());

            $workingProcess = new WorkingProcess();
            $workingProcess->setPid($pid);
            $workingProcess->setWorker($worker);
            $this->workingProcesses[] = $workingProcess;
        }
    }

    /**
     * 停止 worker
     * @param Worker $worker
     */
    private function stopWorker(Worker $worker)
    {
        $index = $this->getIndexOfWorkingProcesses($worker->getId());
        if (false !== $index) {
            $this->workingProcesses[$index]->setStopping(true);
        }
    }

    /**
     *
     * @param $workerId
     * @return bool|int|string
     */
    private function getIndexOfWorkingProcesses($workerId)
    {
        foreach ($this->workingProcesses as $index => $workingProcess) {
            if ($workerId == $workingProcess->getWorker()->getId()) {
                return $index;
            }
        }
        return false;
    }

    /**
     * @param $pid
     * @return bool|int|string
     */
    private function getIndexOfWorkingProcessesByPid($pid)
    {
        foreach ($this->workingProcesses as $index => $workingProcess) {
            if ($pid == $workingProcess->getPid()) {
                return $index;
            }
        }
        return false;
    }


    /**
     * 解析配置文件
     */
    private function parseConfig()
    {
        if (is_readable($this->configPath)) {
            $iniConfig = parse_ini_file($this->configPath, true);

            $this->workers = [];
            foreach ($iniConfig as $id => $item) {
                $command = strval($item["command"] ?? "");
                $enabled = boolval($item["enabled"] ?? false);

                $worker = new Worker();
                $worker->setId($id);
                $worker->setEnabled($enabled);
                $worker->setCommand($command);
                $this->workers[] = $worker;
            }
        }
    }

    /**
     * 创建子进程，并返回子进程 id
     * @param $command
     * @return int
     */
    private function createWorker($command)
    {
        $process = new Process(function (Process $worker) use ($command) {
            $worker->exec('/bin/sh', ['-c', $command]);
        });
        return $process->start();
    }


}