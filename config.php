<?php
/**
 * 配置文件
 * User: xiebin
 * Date: 16/4/20
 * Time: 上午9:38
 */

defined('WWW_ROOT') ? '' : define('WWW_ROOT', dirname(__FILE__));

//MYSQL 连接信息
defined('MYSQL_HOST') ? '' : define('MYSQL_HOST', '10.100.133.113');
defined('MYSQL_USER') ? '' : define('MYSQL_USER', 'tianan');
defined('MYSQL_PASSWORD') ? '' : define('MYSQL_PASSWORD', 'tianan');
defined('MYSQL_DBNAME') ? '' : define('MYSQL_DBNAME', 'ecore_dat');
defined('MYSQL_CHARSET') ? '' : define('MYSQL_CHARSET', 'utf8');

//MONGODB 连接信息
defined('MONGODB_HOST') ? '' : define('MONGODB_HOST', '127.0.0.1');
defined('MONGODB_PORT') ? '' : define('MONGODB_PORT', '27017');

//分页信息
defined('PAGE_SIZE') ? '' : define('PAGE_SIZE', 1000);

