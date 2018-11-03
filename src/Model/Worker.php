<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 02/11/2018
 * Time: 10:24 PM
 */

namespace Lvinkim\Daemon\Model;

class Worker
{
    /**
     * 工作进程 id
     * @var string
     */
    private $id;

    /**
     * 真正执行的 command 命令
     * @var string
     */
    private $command;

    /**
     * 是否启用
     * @var bool
     */
    private $enabled;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

}