<?php

class LaiKe {
    var $db_read;

    function __construct($arr = array("db_read" => null)) {
        $this->db_read = $arr["db_read"];
    }

    function getPartnerStoreId($store_id) {
        $sql1 = "select source_store_id from nowmss.ecm_store s  where s.store_id=" . $store_id;
        $storeInfo = $this->db_read->getRow($sql1, array(), DB_FETCHMODE_ASSOC);
        $sql2 = "select * from crm.crm_store_attribute where store_id=" . $storeInfo['source_store_id'];
        $Info = $this->db_read->getRow($sql2, array(), DB_FETCHMODE_ASSOC);
        return $Info;
    }

    function getOrderInfo($order_id) {
        $sql1 = "select o.order_amount,o.payment_name,o.orignalorder_amount,o.order_id,o.order_sn,o.postscript,o.seller_id,e.shipping_fee,e.consignee,e.address,e.phone_mob,e.request_time from nowmss.ecm_order o left join nowmss.ecm_order_extm e on o.order_id=e.order_id where o.order_id=" . $order_id;
        $userInfo = $this->db_read->getRow($sql1, array(), DB_FETCHMODE_ASSOC);
        $sql2 = "select goods_name,spec_id,quantity,discount,price,packing_fee from nowmss.ecm_order_goods where order_id=" . $order_id;
        $userInfo['order_goods'] = $this->db_read->getAll($sql2, array(), DB_FETCHMODE_ASSOC);
        return $userInfo;
    }

    /**
     * 获取订单发送表ecm_order_send信息
     *
     * @param    int            order_id    订单ID
     *
     * @author    HeZhuang
     * @time    2015/06/19
     * @return    array
     */
    function getEcmOrderSend($order_id) {
        $sendInfo = array();
        try {
            $sql = "SELECT * FROM nowmss.ecm_order_send WHERE order_id = " . $order_id . " ORDER BY id DESC LIMIT 1";
            $sendInfo = $this->db_read->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        } catch (Exception $e) {
            return $sendInfo;
        }
        return $sendInfo;
    }

    function makeSign($arr) {
        ksort($arr);
        foreach ($arr as $k => $val) {
            $str .= $k . '=' . $val . '\n';
        }
        $str = trim($str, '\n') . "bS3obu855FYK632IkpDraCcP2Ru9r7we8w90CUwt";
        return hash_hmac('sha256', $str, 'secret');
    }

    function makeUrlInfo() {
        $arr['_u'] = ORDER_TOLAIKE_U;
        $arr['_v'] = "1.0.2345";
        $arr['_c'] = ORDER_TOLAIKE_U;
        $arr['_@'] = time();
        $url = ORDER_TOLAIKE_URI . '?_u=' . $arr['_u'] . '&_v=' . $arr['_v'] . '&_c=' . $arr['_c'] . '&_@=' . $arr['_@'] . '&_s=' . $this->makeSign($arr);
        return $url;
    }

    function sendOrder($order_id) {
        // 来客参数 _session | ecm_order_send.order_id 拼接 ecm_order_send.id | HeZhuang 2015/06/19
        $order_send_info = $this->getEcmOrderSend($order_id);
        $arr['_session'] = $order_send_info['order_id'] . $order_send_info['id'];

        file_put_contents("/data/bin/crontab/log/sendOrder.txt", "\n" . date("Y-m-d H:i:s") . " ---------------------------- start ---------------------------- " . "\n", FILE_APPEND);
        file_put_contents("/data/bin/crontab/log/sendOrder.txt", $order_send_info['order_id'], FILE_APPEND);
        file_put_contents("/data/bin/crontab/log/sendOrder.txt", $arr['_session'], FILE_APPEND);

        $order_info = $this->getOrderInfo($order_id);
        $partner_info = $this->getPartnerStoreId($order_info["seller_id"]);
        $arr['sid'] = $partner_info['partner_store_id'];
        $arr['xid'] = $order_info['order_id'];
        $order_goods = array();
        $packing_fee = 0;
        $remark = "\r\n姓名:\t" . $order_info['consignee'];
        $remark .= "\r\n地址:\t" . $order_info['address'];
        $remark .= "\r\n电话:\t" . $order_info['phone_mob'];
        $remark .= "\r\n单号:\t" . $order_info['order_sn'];
        $remark .= "\r\n******************";
        $remark .= "\r\n名称 \t\t单价\t数量 ";
        foreach ($order_info['order_goods'] as $v) {
            if ($v['discount'] > 0) {
                $order_goods[] = array(
                    "name" => $v['goods_name'],
                    "type" => 0,
                    "count" => $v['quantity'],
                    "price" => $v['price'] * $v['discount']
                );
                $packing_fee += $v['packing_fee'] * $v['quantity'];
            }
            $remark .= "\r\n " . $v['goods_name'] . "\t";
            if (strlen($v['goods_name']) < 10) {
                $remark .= "\t";
            }
            $remark .= $v['price'] . "\t" . $v['quantity'];
        }
        if ($packing_fee > 0) {
            $order_goods[] = array(
                "name" => "包装费",
                "type" => 0,
                "count" => $packing_fee,
                "price" => 1
            );
            $remark .= "\r\n 包装费  \t " . $packing_fee;
        }
        $remark .= "\r\n 配送费：\t" . $order_info['shipping_fee'];
        $remark .= "\r\n订单总额:\t" . $order_info['order_amount'];
        if ($order_info['payment_name'] == "在线支付") {
            $remark .= "\r\n已在线付款:\t" . $order_info['orignalorder_amount'];
        }
        $arr['body'] = json_encode($order_goods);
        $xtra = array(
            "rem" => $order_info['postscript'] . "\r\n \r\n------------------------------\r\n" . $remark,
            //"ttl"=>600,
            "sal" => $order_info['consignee'],
            "add" => $order_info['address'],
            "phn" => $order_info['phone_mob'],
            "exp" => $order_info['request_time']
        );
        $arr['xtra'] = json_encode($xtra);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->makeUrlInfo());  //Refund');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
