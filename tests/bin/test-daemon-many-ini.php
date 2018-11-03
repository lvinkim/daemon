<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 03/11/2018
 * Time: 12:49 AM
 */

use Lvinkim\Daemon\DaemonMany;

require dirname(__DIR__) . "/../vendor/autoload.php";

$configPath = dirname(__DIR__) . "/config/daemon.ini";

$daemonMany = new DaemonMany($configPath, true);
$daemonMany->run();

