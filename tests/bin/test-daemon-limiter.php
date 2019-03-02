<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2019/2/15
 * Time: 2:08 PM
 */

use Lvinkim\Daemon\Model\Task;
use Lvinkim\Daemon\TaskLimiter;

require dirname(__DIR__) . "/../vendor/autoload.php";

$taskBuilder = function () {

    $id = time();

    $script = dirname(__DIR__) . "/worker/task.php";

    $command = "/usr/bin/env php {$script} {$id}";
    $task = new Task();
    $task->setId($id);
    $task->setCommand($command);

    printf("【任务生成器】生成任务 %s\n", $id);

    return $task;
};

$maxProcess = 3;
$generateInterval = 10;

$daemonTask = new TaskLimiter($taskBuilder, $maxProcess, $generateInterval, false);

$daemonTask->run();

