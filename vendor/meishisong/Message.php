<?php

class Message_Info {
    var $wdb;
    var $rdb;
    var $log;
    var $arrp;

    public function __construct($arr) {
        $this->wdb = $arr['wdb'];
        $this->rdb = $arr['rdb'];
        $this->log = $arr['log'];
    }

    public function setDB($arr) {
        $this->wdb = $arr['wdb'];
        $this->rdb = $arr['rdb'];
    }

    // type 发送短信的系统 | data array 存了发送的信息 
    public function messageContent($type, $data) {
        switch ($type) {
            case 'cs':
                // $message = "尊敬的".$data['consignee']."，您的「".$data['partner']."外卖」订单正在派送中，请耐心等候，稍后收到餐品确认无误时，请将此验证码 ".$data['code']." 告知配送员，配送员".$data['empname']."，联系电话".$data['emp_mobile'];
                $message = "尊敬的" . $data['consignee'] . "，您的「" . $data['partner'] . "外卖」订单正在派送中，请耐心等候，配送员" . $data['empname'] . "，联系电话" . $data['emp_mobile'];
                break;
        }
        return $message;
    }
}
