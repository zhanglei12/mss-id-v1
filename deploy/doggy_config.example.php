<?php
/**
 * 将当前项目的配置转换为Doggy框架需要的配置
 */

//fallback settings
if (!defined('DEFAULT_LOG_PATH')) {
    define('DEFAULT_LOG_PATH', '/tmp/dev.log');
}
if (!defined('LOG_LEVEL')) {
    define('LOG_LEVEL', 'debug');
}
if (!defined('MEMCACHED_HOST')) {
    define('MEMCACHED_HOST', '127.0.0.1');
}
if (!defined('REDIS_DSN')) {
    define('REDIS_DSN', '127.0.0.1:6379');
}
if (!defined('MYSQL_DB_DSN')) {
    define('MYSQL_DB_DSN', 'mysql://' . DATABASE_48_USERNAME . ':' . DATABASE_48_PASSWORD . "@" . DATABASE_48_HOST . ":" . DATABASE_48_PORT . "/" . DATABASE_48_NAME_NOWMSS . '?charset=utf8');
}
if (!defined('GEARMAN_SERVERS')) {
    define('GEARMAN_SERVERS', '127.0.0.1:4730');
}
Doggy_Config::add(array(
    // logging settings
    'app.log.default' =>
        array(
            'class' => 'Doggy_Log_FileLog',
            'options' =>
                array(
                    'output' => DEFAULT_LOG_PATH,
                    'level' => LOG_LEVEL,
                ),
        ),
    'app.log.trace' =>
        array(
            'class' => 'Doggy_Log_FileLog',
            'options' =>
                array(
                    'output' => DEFAULT_LOG_PATH,
                    'level' => LOG_LEVEL,
                ),
        ),
    // database
    //'app.db.default' => 'mysql://root:root@localhost/passport?charset=utf8',
    'app.db.default' => MYSQL_DB_DSN,
    // cache
    'app.cache.memcached.default' =>
        array(
            'servers' =>
                array(
                    0 =>
                        array(
                            'host' => MEMCACHED_HOST,
                            'port' => 11211,
                            'weight' => 1,
                        ),
                ),
        ),
    'app.redis_host' => REDIS_DSN,
    'app.gearman_servers' => GEARMAN_SERVERS,
    'app.biz.queue_partners' => explode(',', DEAL_PARTNER_ORDER),
    'app.mobile_api.key' => 'fbfeea2d3f2a489d0e9da11e759fdd86',
    'app.mobile_api.partner_id' => '100031',
));

