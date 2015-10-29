<?php

class Diff_price {
    var $wdb;
    var $rdb;
    var $log;

    public function __construct($rdb) {
        $this->rdb = $rdb;
    }

    function diffAllActualReceipt($orderIds) {
        if ($orderIds == null || empty($orderIds)) {
            return false;
        }
        $sql = 'select * from ecm_order where status=50 and order_id in("' . $orderIds . '")';
        $res = $this->rdb->query($sql);
        $arr = array();
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            if ($row['if_pay'] == 1) {
                if ($row['actual_receipt'] != '0.00') {
                    $arr[$row['order_id']] = array(
                        'order_id' => $row['order_id'],
                        'disPrice' => $row['actual_receipt']
                    );
                    $reason = $this->getReason($row['order_id'], 7);
                    $arr[$row['order_id']]['reason'] = $reason ? $reason : '';
                }
            }
            else {
                if ($row['actual_receipt'] != $row['orignalorder_amount']) {
                    $arr[$row['order_id']] = array(
                        'order_id' => $row['order_id'],
                        'disPrice' => ($row['actual_receipt'] - $row['orignalorder_amount'])
                    );
                    $reason = $this->getReason($row['order_id'], 7);
                    $arr[$row['order_id']]['reason'] = $reason ? $reason : '';
                }
            }
        }
        return $arr;
    }

    function diffActualReceipt($orderId) {
        $sql = 'select * from ecm_order where status=50 and order_id=' . $orderId;
        $row = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if (!$row) {
            return false;
        };
        if ($row['if_pay'] == 1) {
            if ($row['actual_receipt'] != '0.00') {
                $arr = array(
                    'order_id' => $row['order_id'],
                    'disPrice' => $row['actual_receipt']
                );
                $reason = $this->getReason($row['order_id'], 7);
                $arr['reason'] = $reason ? $reason : '';
            }
        }
        else {
            if ($row['actual_receipt'] != $row['orignalorder_amount']) {
                $arr = array(
                    'order_id' => $row['order_id'],
                    'disPrice' => ($row['actual_receipt'] - $row['orignalorder_amount'])
                );
                $reason = $this->getReason($row['order_id'], 7);
                $arr['reason'] = $reason ? $reason : '';
            }
        }
        return $arr;
    }

    function getReason($orderId, $type) {
        $sql = 'select * from ecm_order_reason where order_id =' . $orderId . ' and reason_type =' . $type;
        $row = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if (!$row) {
            return false;
        };
        $dsql = 'select * from ecm_dictionary where dic_type =' . $type . ' and  dic_value=' . $row['reason_value'];
        $drow = $this->rdb->getRow($dsql, array(), DB_FETCHMODE_ASSOC);
        if (!$drow) {
            return false;
        }
        return $drow['dic_name'];
    }
}

?>
