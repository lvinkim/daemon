# daemon

根据配置文件守护多个使用 shell 命令行启动的进程，并能自动重载配置。


## 安装
```
$ composer require lvinkim/daemon
```

### 使用说明

##### 1. 配置文件

```
$ vi /var/www/html/daemon.ini

[worker:1]
command = "/usr/bin/env php /var/www/html/tests/worker/worker-1.php"
enabled = 1

[worker:2]
command = "/usr/bin/env php /var/www/html/tests/worker/worker-2.php"
enabled = 1

[worker:3]
command = "/usr/bin/env php /var/www/html/tests/worker/worker-3.php"
enabled = 1

```

##### 2. 编写守护脚本
```
$ vi /var/www/html/daemon.php

<?php
use Lvinkim\Daemon\DaemonMany;

require dirname(__DIR__) . "/vendor/autoload.php";

$configPath = "/var/www/html/daemon.ini";

$daemonMany = new DaemonMany($configPath, true);
$daemonMany->run();
``` 

#### 3. 执行守护脚本
```
$ php /var/www/html/daemon.php
启用 worker:1
启用 worker:2
启用 worker:3
.... 当其中一个 worker 执行完毕，会自动拉起一个新的进程，再次执行此 worker ...
```


#### 4. 调整配置

```
比如要停止 worker:2 , 只需要将 worker:2 的 enabled 配置为 0 
$ vi /var/www/html/daemon.ini

...

[worker:2]
command = "/usr/bin/env php /var/www/html/tests/worker/worker-2.php"
enabled = 0

...

```

不需要任务操作，守护会在 15 秒内自动更新配置，新配置将不再守护 worker:2 ，即当前 worker:2 结束后，不再重启

#### 一些说明: 

* 如果要重新启用 worker:2 , 只需要将 worker:2 的 enabled 重新配置为 1
* 如果要新增 worker , 只需要将新配置在配置文件后追加
* 如果不希望让进程自动重载配置可使用 `DaemonMany($configPath, false)` 
* 在手动重载配置的模式下，更新配置后，需要手动向进程发送 SIGHUP 信息，即: `kill -SIGHUP {daemon-pid}`，其中 `daemon-pid` 可通过 `ps` 命令得到

