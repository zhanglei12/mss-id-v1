<?php

class Mss_Partner {
    var $wdb;
    var $rdb;
    var $log;

    public function __construct($arr) {
        $this->wdb = $arr['wdb'];
        $this->rdb = $arr['rdb'];
        $this->log = $arr['log'];
    }

    public function getPartnerByAppKey($app_key) {
        $sql = "select * from ecm_cooperate where appkey=" . $app_key;
        return $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
    }
}