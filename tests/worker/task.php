<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2019/2/15
 * Time: 12:12 PM
 */

$id = strval($argv[1] ?? 0);

$filename = dirname(__DIR__) . "/../var/task-{$id}.task";
foreach (range(1, 20) as $item) {
    file_put_contents($filename, $item . " : " . $id . PHP_EOL, FILE_APPEND);
    sleep(1);
}