<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2019/2/15
 * Time: 1:36 PM
 */

namespace Lvinkim\Daemon\Model;


class Task
{
    /**
     * 任务唯一号
     * @var string
     */
    private $id;

    /**
     * 进程号
     * @var string
     */
    private $pid = 0;

    /**
     * 是否可用
     * @var bool
     */
    private $enabled = true;

    /**
     * 真正执行的 command 命令行
     * @var string
     */
    private $command;

    /**
     * @return string
     */
    public function getId(): string
    {
        return strval($this->id ?? "");
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getPid(): string
    {
        return strval($this->pid ?? 0);
    }

    /**
     * @param string $pid
     */
    public function setPid(string $pid): void
    {
        $this->pid = $pid;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return boolval($this->enabled ?? false);
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return strval($this->command ?? "");
    }

    /**
     * @param string $command
     */
    public function setCommand(string $command): void
    {
        $this->command = $command;
    }


}