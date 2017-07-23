<?php
error_reporting('1');
define('DEBUG', 'on');
define("WEBPATH", realpath(__DIR__));
date_default_timezone_set('PRC');

require __DIR__.'/libs/lib_config.php';

$AppSvr = new Swoole\Protocol\AppServer();
$AppSvr->loadSetting(__DIR__."/swoole.ini"); //加载配置文件
$AppSvr->setAppPath(__DIR__.'/apps/'); //设置应用所在的目录
$AppSvr->setLogger(new Swoole\Log\EchoLog(false)); //Logger

/**
 *如果你没有安装swoole扩展，这里还可选择
 * BlockTCP 阻塞的TCP，支持windows平台，需要将worker_num设为1
 * SelectTCP 使用select做事件循环，支持windows平台，需要将worker_num设为1
 * EventTCP 使用libevent，需要安装libevent扩展
 */
$server = new \Swoole\Network\Server('127.0.0.1', 9502);
$server->setProtocol($AppSvr);
// $server->daemonize(); //作为守护进程
$server->run(array('worker_num' => 10, 'max_request' => 10000, 'task_worker_num'=>2));
