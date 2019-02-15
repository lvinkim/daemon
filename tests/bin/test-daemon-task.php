<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2019/2/15
 * Time: 2:08 PM
 */

use Lvinkim\Daemon\DaemonTask;
use Lvinkim\Daemon\Model\Task;

require dirname(__DIR__) . "/../vendor/autoload.php";

$taskGenerator = function () {

    $dbFile = dirname(__DIR__) . "/../var/task.db";
    $script = dirname(__DIR__) . "/worker/task.php";

    $rows = file($dbFile, FILE_IGNORE_NEW_LINES);
    foreach ($rows as $row) {
        $object = json_decode($row);
        $id = intval($object->id ?? time());

        $command = "/usr/bin/env php {$script} {$id}";
        $task = new Task();
        $task->setId($id);
        $task->setCommand($command);

        printf("生成任务 %s\n", $id);

        yield $task;
    }
};
$maxProcess = 3;
$generateInterval = 10;

$daemonTask = new DaemonTask($taskGenerator, $maxProcess, $generateInterval, false);

$daemonTask->run();

