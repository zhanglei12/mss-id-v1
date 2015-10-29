<?php
if (!defined('MSS_API_APP')) {
    die('invalid request');
}
session_start();
define('STATIC_URI', 'http://static.meishisong.pc');
define("WEB_PATH", 'http://crm.meishisong.pc');
define("API_URL", 'http://www.meishisong.mobi');
// deploy dir
define('APP_SRC_DIR', APP_PATH . '/application');
define('APP_CONF_DIR', APP_PATH . '/deploy');
define('LIB_VENDOR_DIR', APP_PATH . '/vendor');
define('LIB_PEAR_DIR', APP_PATH . '/vendor/pear');
define('APP_MODE', 'prod');

error_reporting(E_ERROR | E_PARSE);
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

//
$LOG_MAIN_CONFIG = '';
$APP_LIBRARY_FILES = array(
    'Smarty_Adapter',
    'crmbase',
    'status',
);
$APP_PRELOAD_VENDOR_FILES = array(
    'phpmailer/class.phpmailer',
    'sms/sms',
    'base/base',
    'httprequest/httprequest',
    'meishisong/address',
);

$APP_CONFIG_FILES = array(
    'db', 'log', 'mail', 'sms', 'sys_uri', 'partner_uri', 'partner',
);

$PEAR_FILES = array('DB', 'log4php/Logger');

$APP_PRELOAD_FILES = array(
    //pear
    'DB', 'log4php/Logger',
    // vendors
    'phpmailer/class.phpmailer',
    'sms/sms',
    'base/base',
    'httprequest/httprequest',
    'meishisong/address',
    // application constants
    'constants',
);

//Follow three constants should deprecated!
// PUBLIC shared repos
define("LIB_PATH_PHP", LIB_VENDOR_DIR);
define("LIB_PATH_PUBLIC", LIB_VENDOR_DIR);
// public configuration
define("CONF_PATH_PUBLIC", APP_CONF_DIR);
define("CONF_PATH_LIBRARY", APP_SRC_DIR . "/library");


