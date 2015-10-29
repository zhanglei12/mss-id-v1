<?php

class Emp_Info {
    var $wdb;
    var $rdb;
    var $log;
    var $arrp;
    var $table = "nowmss";

    public function __construct($arr) {
        $this->wdb = $arr['wdb'];
        $this->rdb = $arr['rdb'];
        $this->log = $arr['log'];
    }

    public function setDB($arr) {
        $this->wdb = $arr['wdb'];
        $this->rdb = $arr['rdb'];
    }

    /**
     * 获取配送员信息
     *
     * @param emp_id int order_id int
     *
     * @return array (emp_name,emp_mobile)
     */
    public function getEmpInfo($order_id) {
        if (!$order_id) {
            return array();
        }
        $typeSql = "SELECT emp_id,cs_order_type FROM nowmss.ecm_order where order_id = " . $order_id;
        $orderResult = $this->rdb->getRow($typeSql, array(), DB_FETCHMODE_ASSOC);
        $emp_id = $orderResult['emp_id'];
        $cs_order_type = $orderResult['cs_order_type'];
        if ($cs_order_type == 2 || $cs_order_type == 3) {
            $sql = "SELECT b.real_name as emp_name,a.user_phone as emp_mobile FROM cs.cs_user a left join cs.cs_user_extm b on a.user_id = b.user_id WHERE a.user_id = " . $emp_id;
        }
        else if ($cs_order_type == 1) {
            $sql = "SELECT emp_name,emp_mobile FROM nowmss.ecm_employee WHERE emp_id = " . $emp_id;
        }
        $result = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        return $result;
    }
}
