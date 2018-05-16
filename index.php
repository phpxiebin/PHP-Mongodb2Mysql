<?php
/**
 * 单一入口
 * User: phpxiebin
 * Date: 16/4/16
 * Time: 上午11:20
 */
header("Content-type: text/html; charset=utf-8");
set_time_limit(0);
ini_set('memory_limit', '2048M');
error_reporting(E_ERROR | E_WARNING);

require dirname(__FILE__) . '/config.php';
require WWW_ROOT . '/lib/mongodb.php';
require WWW_ROOT . '/lib/pdo.php';
require WWW_ROOT . '/lib/help.php';
require WWW_ROOT . '/controller/base.php';

$sTime = microtime(true);
$fp = fopen("sync.txt", 'w+');
if (!flock($fp, LOCK_NB + LOCK_EX)) {
    die('正在同步中...' . PHP_EOL);
}

require WWW_ROOT . '/controller/customer.php';
$obj = new Customer();
$log = $obj->run("全部");

fwrite($fp, "执行结果:" . PHP_EOL . $log);
flock($fp, LOCK_UN);
fclose($fp);
$eTime = microtime(true);
echo '执行时间:' . round($eTime - $sTime, 3) . PHP_EOL . '执行结果:' . PHP_EOL . $log;



