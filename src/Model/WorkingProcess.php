<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 03/11/2018
 * Time: 1:28 AM
 */

namespace Lvinkim\Daemon\Model;


class WorkingProcess
{
    /**
     * @var Worker
     */
    private $worker;

    /**
     * @var int
     */
    private $pid;

    /**
     * @var bool
     */
    private $stopping;


    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * @param int $pid
     */
    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    /**
     * @return Worker
     */
    public function getWorker(): Worker
    {
        return $this->worker;
    }

    /**
     * @param Worker $worker
     */
    public function setWorker(Worker $worker): void
    {
        $this->worker = $worker;
    }

    /**
     * @return bool
     */
    public function isStopping(): bool
    {
        return boolval($this->stopping);
    }

    /**
     * @param bool $stopping
     */
    public function setStopping(bool $stopping): void
    {
        $this->stopping = $stopping;
    }

}