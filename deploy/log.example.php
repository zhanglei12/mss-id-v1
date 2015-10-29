<?php
global $LOG_MAIN_CONFIG;
$LOG_MAIN_CONFIG = array(
    'rootLogger' => array(
        'appenders' => array('default'),
    ),
    'appenders' => array(
        'default' => array(
            'class' => 'LoggerAppenderDailyFile',
            'layout' => array(
                'class' => 'LoggerLayoutPattern',
                'params' => array(
                    'conversionPattern' => '%date %logger %-5level %message%newline'
                ),
            ),
            'params' => array(
                'file' => '/data/web/api.meishisong.mobi/file-%s.log',
                'datePattern' => 'Y-m-d',
                //'maxFileSize' => '1MB',
                //'maxBackupIndex' => 5,
            ),
        ),
    ),
);
?>
