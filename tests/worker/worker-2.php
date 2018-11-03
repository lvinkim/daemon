<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 02/11/2018
 * Time: 10:34 PM
 */

$pid = posix_getpid();

$end = mt_rand(5, 10);
echo "\tworker-2 进程 {$pid} 将运行 {$end} 秒" . PHP_EOL;
foreach (range(1, $end) as $value) {
    sleep(1);
    echo "\tworker-2 进程 {$pid} 已运行 {$value} 秒" . PHP_EOL;
}
