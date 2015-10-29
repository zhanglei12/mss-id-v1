<?php

class Mss_Order {
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

    function get_time() {
        list($usec, $sec) = explode(' ', microtime());
        $usec2msec = $usec * 1000;
        if ($usec2msec < 100) {
            $msec = $sec . "0" . (int)$usec2msec;
        }
        else {
            $msec = $sec . (int)$usec2msec;
        }
        return $msec;
    }

    function get_order_sn($partner_id = 100000) {
        return $this->get_time() . $partner_id . rand(100, 999);
    }

    public function updateOrder($arr) {
        try {
            $sql = "INSERT INTO `ecm_cooperate_history` SET `cooperate_id`='" . $arr['cooperate_id'] . "',partner_order_id='" . $arr['partner_order_id'] . "',json_info='" . $arr['json_info'] . "',add_time='" . $arr['add_time'] . "',action='" . $arr['action'] . "'";
            $res = $this->wdb->query($sql);
            $this->arrp = "cooperate_id:" . $arr['cooperate_id'] . ",partner_order_id:" . $arr['partner_order_id'] . ",INSERT:" . $res;
            $this->log->info($this->arrp);
            if (DB::isError($res)) {
                return array('state' => -2, 'message' => '数据库异常', 'data' => $e->getMessage());
            }
            $resid = $this->wdb->getOne("select last_insert_id()");
            if (DB::isError($resid)) {
                return array('state' => -2, 'message' => '数据库异常', 'data' => $e->getMessage());
            }
            return array('state' => 1, 'message' => '插入数据成功', 'data' => array("id" => $resid));
        } catch (Exception $e) {
            return array('state' => -1, 'message' => '未知异常', 'data' => $e->getMessage());
        }
    }

    function getOrderPrice($orderId) {
        $sql = 'SELECT * FROM `ecm_order` WHERE `order_id` =' . $orderId;
        $res = $this->wdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if (!$res) {
            return false;
        }
        $ret = array('order_id' => $res['order_id'], 'actual_receipt' => $res['actual_receipt'], 'actual_expend' => $res['actual_expend']);
        $ret['rec_reason'] = $this->getReason($res['order_id'], 7);
        $ret['exp_reason'] = $this->getReason($res['order_id'], 6);
        return $ret;
    }

    function getEmpInfo($orderId) {
        $sql = 'SELECT * FROM `ecm_order` WHERE `order_id` =' . $orderId;
        $res = $this->wdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if (!$res) {
            return false;
        }
        $esql = 'SELECT * FROM `ecm_employee` WHERE `emp_id` =' . $res['emp_id'];
        $row = $this->wdb->getRow($esql, array(), DB_FETCHMODE_ASSOC);
        if (!$row) {
            return false;
        };
        $ret = array('emp_name' => $row['emp_name'], 'tel' => $row['emp_mobile']);
        return $ret;
    }

    function getRegion($region_id, $num = 6) {
        $region = $region_id;
        for ($i = 0; $i < $num; $i++) {
            $sql = 'select * from nowmss.ecm_region where region_id=' . $region;
            $regionInfo = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
            if ($i == 0) {
                $region_name = $regionInfo['region_name'];
            }
            if ($regionInfo['parent_id'] < 1) {
                $regionInfo['sub_region'] = $region_name;
                return $regionInfo;
                break;
            }
            $region = $regionInfo['parent_id'];
        }
        return $regionInfo;
    }

    function getReason($orderId, $type) {
        $sql = 'select * from ecm_order_reason where order_id =' . $orderId . ' and reason_type =' . $type;
        $row = $this->wdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if (!$row) {
            return false;
        };
        $dsql = 'select * from ecm_dictionary where dic_type =' . $type . ' and  dic_value=' . $row['reason_value'];
        $drow = $this->wdb->getRow($dsql, array(), DB_FETCHMODE_ASSOC);
        if (!$drow) {
            return false;
        }
        return $drow['dic_name'];
    }

    /*获得异常，取消订单原因  */
    function getRemark($orderId) {
        $remark_sql = "SELECT * FROM `ecm_order_log` WHERE `changed_status` IN (0,30) AND `order_id`=" . $orderId;
        $remark = $this->wdb->getRow($remark_sql, array(), DB_FETCHMODE_ASSOC);
        if (!$remark) {
            return false;
        }
        return $remark['remark'];
    }
}
