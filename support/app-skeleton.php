<?php

define("APP_PATH", realpath(dirname(__FILE__) . '/..'));

require APP_PATH.'/appliaction/yaf_utils.php';

yaf_load($APP_PRELOAD_FILES);

$app = new Yaf_Application(APP_PATH . '/conf/application.ini');
$app->bootstrap()->run();
