<?php

class Mss_Amount {
    var $wdb;
    var $rdb;
    var $log;
    var $arrp;
    var $table = "nowmss";
    var $tableOne = "sb";

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
     * 获取订单金额信息
     *
     * @param  int    order_id    订单ID
     * @param  str    display        金额显示端（  '' 非手持端， app 手持端 ）
     * @param  str    status        修改订单金额状态（ shouldReceiptExpend 应收、应付， actualReceipt 实收， actualExpend 实付 ）
     *
     * @return array
     */
    public function getOrderAmountInfo($order_id, $display = '', $status = '') {
        $data = array();
        try {
            $orderResult = $this->getOrderInfo($order_id);
            if ($orderResult['state'] > 0) {
                $order = $orderResult['data'];
            }
            else {
                return $orderResult;
            }
            $goodsResult = $this->getGoodsInfo($order_id);
            if ($goodsResult['state'] > 0) {
                $goods = $goodsResult['data'];
            }
            else {
                return $goodsResult;
            }
            $data['order_id'] = $order['order_id'];
            // $data['goods_amount']		= $order['goods_amount'];		// 应收菜品(ecm_order.goods_amount)
            $data['shipping_fee'] = $order['shipping_fee'];        // 应收配送费(ecm_order_extm.shipping_fee)
            // $data['packing_fee']		= $order['packing_fee'];		// 应收包装费(ecm_order.packing_fee)
            // $data['order_amount']		= $order['order_amount'];		// 应收总额/最终应收金额(ecm_order.order_amount)
            $data['actual_receipt_ga'] = $order['actual_receipt_ga'];    // 实收菜品(ecm_order.actual_receipt_ga)
            $data['actual_receipt_sp'] = $order['actual_receipt_sp'];    // 实收配送费(ecm_order.actual_receipt_sp)
            $data['actual_receipt_pf'] = $order['actual_receipt_pf'];    // 实收包装费(ecm_order.actual_receipt_pf)
            $data['actual_receipt'] = $order['actual_receipt'];        // 实收总额(ecm_order.actual_receipt)
            // $data['buy_amount']			= $order['buy_amount'];			// 应付总额(ecm_order.buy_amount)
            $data['actual_expend_ga'] = $order['actual_expend_ga'];    // 实付菜品(ecm_order.actual_expend_ga)
            $data['actual_packingfee'] = $order['actual_packingfee'];    // 实付包装费(ecm_order.actual_packingfee)
            $data['actual_expend'] = $order['actual_expend'];        // 实付总额(ecm_order.actual_expend)
            // $data['prefer_fee']			= $order['prefer_fee'];			// 总额调整 ，用于记录修改/删除菜品时金额的变动(ecm_order.prefer_fee)
            $data['orignalorder_amount'] = $order['orignalorder_amount'];// 原始应收金额 ，即为原来的order_amount(ecm_order.orignalorder_amount)

            $data['goods_amount'] = 0;
            $data['packing_fee'] = 0;
            $data['should_expend_ga'] = 0;
            $data['should_expend_pf'] = 0;
            // 菜品相关金额
            foreach ($goods as $goodsV) {
                $goodsV['from_partner'] = $order['from_partner'];
                $data['goods_amount'] += $goodsV['quantity'] * $goodsV['price'];            // 应收菜品 | 菜品金额 = 菜品数量*单价
                // $data['packing_fee']	+= $goodsV['quantity'] * $goodsV['packing_fee'];	// 应收包装费 | 包装费 = 菜品数量*包装费
                $data['packing_fee'] += $this->getPackingFee($goodsV);    // 应收包装费 | 包装费 = 菜品数量*包装费
                $data['should_expend_ga'] += $goodsV['quantity'] * $goodsV['price'] * $goodsV['discount'];// 应付菜品 | 菜品金额 = 菜品数量*单价*折扣
                // $data['should_expend_pf']	+= $goodsV['quantity'] * $goodsV['packing_fee'];// 应付包装费 | 包装费 = 菜品数量*包装费
                $data['should_expend_pf'] += $this->getPackingFee($goodsV);// 应付包装费 | 包装费 = 菜品数量*包装费
            }
            $data['order_amount'] = $data['goods_amount'] + $data['shipping_fee'] + $data['packing_fee'];    // 应收总额 | 应收总额 = 应收菜品+应收配送费+应收包装费
            $data['buy_amount'] = $data['should_expend_ga'] + $data['should_expend_pf'];// 应付总额 | 应付总额 = 应付菜品+应付包装费
            $data['prefer_fee'] = $data['order_amount'] - $data['orignalorder_amount'];    // 总额调整 | 总额调整 = 应收总额-原始应收金额

            // 若为手持端获取数据，则应付应收金额不做计算，实付实收金额做计算
            if ($display == 'app') {
                $data['goods_amount'] = $order['goods_amount'];    // 应收菜品
                $data['packing_fee'] = $order['packing_fee'];    // 应收包装费
                $data['order_amount'] = $order['order_amount'];    // 应收总额/最终应收金额
                $data['buy_amount'] = $order['buy_amount'];        // 应付总额
                $data['prefer_fee'] = $order['prefer_fee'];        // 总额调整 ，用于记录修改/删除菜品时金额的变动

                $data['actual_expend_ga'] = $data['should_expend_ga'];// 实付菜品 = 应付菜品
                $data['actual_packingfee'] = $data['should_expend_pf'];// 实付包装费 = 应付包装费
                $data['actual_expend'] = $data['buy_amount'];        // 实付总额 = 应付总额
                $data['actual_receipt_ga'] = $data['goods_amount'];    // 实收菜品 = 应收菜品
                $data['actual_receipt_sp'] = $data['shipping_fee'];    // 实收配送费 = 应收配送费
                $data['actual_receipt_pf'] = $data['packing_fee'];        // 实收包装费 = 应收包装费
                $data['actual_receipt'] = $data['order_amount'];    // 实收总额 = 应收总额
                // 在线支付订单
                if ($order['payment_name'] == '在线支付') {
                    $data['actual_receipt_ga'] = $data['order_amount'];// 实收菜品 = 应收总额
                    $data['actual_receipt_sp'] = 0;                    // 实收配送费 = 0
                    $data['actual_receipt_pf'] = 0;                    // 实收包装费 = 0
                }
            }

            // 嗷嗷抢单计算实付实收
            if ($order['from_partner'] == '100042' && $order['payment_name'] == '在线支付') {
                // $data['actual_expend']	= $order['buy_amount'] - $order['order_amount'];	// 实付总额 = 应付总额 - 应收总额
                $data['actual_expend'] = $data['buy_amount'] - $data['order_amount'];    // 实付总额 = 应付总额 - 应收总额
                $data['actual_receipt'] = 0;    // 实收总额 = 0
            }

            // 来客计算实付实收
            if ($order['from_partner'] == '100039' && $order['payment_name'] == '在线支付') {
                // if($order['order_id'] == 8656598)
                // $data['buy_amount']	= $order['buy_amount'] - $order['goods_amount'];	// 应付总额 = 应付总额 - 应收菜品
                $data['actual_expend'] = $data['buy_amount'] - $data['order_amount'];    // 实付总额 = 应付总额 - 应收菜品
                $data['actual_receipt'] = 0;    // 实收总额 = 0
            }

            // 是否更新数据
            if (!empty($status)) {
                $res = $this->updateOrderAmountInfo($order_id, $data, $status);
                if ($res['state'] > 0) {
                    return $res;
                }
                else {
                    return array('state' => 2, 'message' => '获取数据成功,未更新成功', 'data' => $data);
                }
            }

            // 支付方式为在线支付 | 应收总额 = 应收总额-原始应收金额
            $order['order_amount'] = $data['order_amount'];
            $data['order_amount'] = $this->getOrderAmount($order);
            // 餐厅月付、预付、现付
            $order['buy_amount'] = $data['buy_amount'];
            $data['buy_amount'] = $this->getBuyAmount($order);
            $order['actual_expend'] = $data['actual_expend'];
            $data['actual_expend'] = $this->getActualExpen($order);
            return array('state' => 1, 'message' => '获取数据成功', 'data' => $data);
        } catch (Exception $e) {
            return array('state' => -1, 'message' => '未知异常', 'data' => $e->getMessage());
        }
    }

    /**
     * 更新订单金额信息
     *
     * @param  int        order_id订单ID
     * @param  array    arr        订单金额相关数组
     * @param  str        status    修改订单金额状态（ shouldReceiptExpend 应收、应付， actualReceipt 实收， actualExpend 实付 ）
     *
     * @return array
     */
    public function updateOrderAmountInfo($order_id, $arr, $status) {
        try {
            switch ($status) {
                case 'shouldReceiptExpend':
                    $tableNameArr = array(
                        'ecm_order' => array(
                            'goods_amount',
                            'packing_fee',
                            'order_amount',
                            'buy_amount',
                            'prefer_fee'
                        )
                    );
                    $setField = $this->joinsTableKeyValue($tableNameArr, $arr);
                    return $this->updateOrderInfo($order_id, $setField);
                    break;
                case 'actualReceipt':
                    $tableNameArr = array(
                        'ecm_order' => array(
                            'actual_receipt_ga',
                            'actual_receipt_sp',
                            'actual_receipt_pf',
                            'actual_receipt'
                        )
                    );
                    $setField = $this->joinsTableKeyValue($tableNameArr, $arr);
                    return $this->updateOrderInfo($order_id, $setField);
                    break;
                case 'actualExpend':
                    $tableNameArr = array(
                        'ecm_order' => array(
                            'actual_expend_ga',
                            'actual_packingfee',
                            'actual_expend'
                        )
                    );
                    $setField = $this->joinsTableKeyValue($tableNameArr, $arr);
                    return $this->updateOrderInfo($order_id, $setField);
                    break;
                default:
                    break;
            }
            return array('state' => 1, 'message' => '更新数据成功', 'data' => $arr);
        } catch (Exception $e) {
            return array('state' => -1, 'message' => '未知异常', 'data' => $e->getMessage());
        }
    }

    /**
     * 把传入的数组重组为以原数组键名和键值相连为字符串.
     *
     * @param array    tableNameArr一个由数据库名对应字段的数组
     * @param array    arr            一个数组
     *
     * @return Array
     */
    function joinsTableKeyValue($tableNameArr, $arr) {
        $joins = '';
        foreach ($arr as $k => $v) {
            foreach ($tableNameArr as $tableName => $tableField) {
                if (in_array($k, $tableField)) {
                    $joins .= $tableName . '.`' . $k . '` = ' . "'" . addslashes(($v === null || $v == "") ? '' : $v) . "',";
                    continue;
                }
            }
        }
        return trim($joins, ',');
    }

    /**
     * 更新订单金额信息
     *
     * @param int    order_id    订单ID
     * @param str    setField    更新字段
     *
     * @return Array
     */
    function updateOrderInfo($order_id, $setField) {
        $sql = "UPDATE " . $this->table . ".ecm_order
			JOIN " . $this->table . ".ecm_order_extm ON ecm_order.order_id = ecm_order_extm.order_id
			SET " . $setField . "
			WHERE ecm_order.order_id = '" . $order_id . "'";
        $res = $this->wdb->query($sql);
        if (DB::isError($res)) {
            return array('state' => -2, 'message' => '数据库异常', 'data' => $e->getMessage());
        }
        return array('state' => 1, 'message' => '更新数据成功', 'data' => $order_id);
    }

    /**
     * 获取订单详细信息
     *
     * @param  int    order_id    订单ID
     *
     * @return array
     */
    public function getOrderInfo($order_id) {
        try {
            $orderSql = "SELECT A.*, B.*
				FROM " . $this->table . ".ecm_order A
				JOIN " . $this->table . ".ecm_order_extm B on A.order_id = B.order_id
				WHERE A.order_id = '" . $order_id . "'";
            $data = $this->rdb->getRow($orderSql, array(), DB_FETCHMODE_ASSOC);
            return array('state' => 1, 'message' => '获取数据成功', 'data' => $data);
        } catch (Exception $e) {
            return array('state' => -1, 'message' => '未知异常', 'data' => $e->getMessage());
        }
    }

    /**
     * 获取订单菜品详细信息
     *
     * @param  int    order_id    订单ID
     *
     * @return array
     */
    public function getGoodsInfo($order_id) {
        try {
            $goodsSql = "SELECT A.*, B.member_price, B.nreceipt_discount, B.receipt_discount, B.default_spec
				FROM " . $this->table . ".ecm_order_goods A
				LEFT JOIN " . $this->table . ".ecm_goods B ON A.goods_id = B.goods_id
				WHERE A.order_id = '" . $order_id . "'";
            $data = $this->rdb->getAll($goodsSql, array(), DB_FETCHMODE_ASSOC);
            return array('state' => 1, 'message' => '获取数据成功', 'data' => $data);
        } catch (Exception $e) {
            return array('state' => -1, 'message' => '未知异常', 'data' => $e->getMessage());
        }
    }

    /**
     * 送餐员结账单张订单的应收金额
     * order_amount 数据库应收
     * payment_name 结算方式
     */
    public function getOrderAmount($arr) {
        $order_amount = $arr['order_amount'];
        if ($arr['payment_name'] == "在线支付") {
            $order_amount = $arr['order_amount'] - $arr['orignalorder_amount'];
        }
        if ($arr['mss_payment_name'] == "POS付款") {
            $order_amount = 0;
        }
        return $order_amount;
    }

    /**
     * 送餐员结账单张订单的实收金额
     * order_amount 数据库应收
     * payment_name 结算方式
     */
    public function getActualReceipt($arr) {
        $actual_receipt = $arr['actual_receipt'];
        if ($arr['mss_payment_name'] == "POS付款") {
            $actual_receipt = 0;
        }
        return $actual_receipt;
    }

    /**
     * 送餐员结账时单张订单应付金额
     */
    public function getBuyAmount($arr) {
        $buy_amount = 0;
        if ($arr['store_balance'] == 3) {
            $buy_amount = $arr['buy_amount'];
        }
        return $buy_amount;
    }

    /**
     * 送餐员结账单张订单实付金额
     */
    public function getActualExpen($arr) {
        $buy_amount = 0;
        if ($arr['store_balance'] == 3) {
            $buy_amount = $arr['actual_expend'];
        }
        return $buy_amount;
    }

    /**
     * 计算包装费，美食送订单（包装费 = 包装费），其他合作伙伴订单（包装费 = 菜品数量*包装费）
     */
    public function getPackingFee($arr) {
        $packing_fee = 0;
        if ($arr['from_partner'] == '' || $arr['from_partner'] == '100005') {
            $packing_fee = $arr['packing_fee'];
        }
        else {
            $packing_fee = $arr['quantity'] * $arr['packing_fee'];
        }
        return $packing_fee;
    }

    public function updateOrder($arr) {
        try {
            $sql = "INSERT INTO " . $this->table . ".`ecm_cooperate_history` SET `cooperate_id`='" . $arr['cooperate_id'] . "',partner_order_id='" . $arr['partner_order_id'] . "',json_info='" . $arr['json_info'] . "',add_time='" . $arr['add_time'] . "',action='" . $arr['action'] . "'";
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
        $sql = 'SELECT * FROM ' . $this->table . '.`ecm_order` WHERE `order_id` =' . $orderId;
        $res = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if (!$res) {
            return false;
        }
        $ret = array('order_id' => $res['order_id'], 'actual_receipt' => $res['actual_receipt'], 'actual_expend' => $res['actual_expend']);
        $ret['rec_reason'] = $this->getReason($res['order_id'], 7);
        $ret['exp_reason'] = $this->getReason($res['order_id'], 6);
        return $ret;
    }

    function getEmpInfo($orderId) {
        $sql = 'SELECT * FROM ' . $this->table . '.`ecm_order` WHERE `order_id` =' . $orderId;
        $res = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if (!$res) {
            return false;
        }
        $esql = 'SELECT * FROM ' . $this->table . '.`ecm_employee` WHERE `emp_id` =' . $res['emp_id'];
        $row = $this->rdb->getRow($esql, array(), DB_FETCHMODE_ASSOC);
        if (!$row) {
            return false;
        };
        $ret = array('emp_name' => $row['emp_name'], 'tel' => $row['emp_mobile']);
        return $ret;
    }

    function getReason($orderId, $type) {
        $sql = 'select * from ' . $this->table . '.ecm_order_reason where order_id =' . $orderId . ' and reason_type =' . $type;
        $row = $this->rdb->getRow($sql, array(), DB_FETCHMODE_ASSOC);
        if (!$row) {
            return false;
        };
        $dsql = 'select * from ' . $this->table . '.ecm_dictionary where dic_type =' . $type . ' and  dic_value=' . $row['reason_value'];
        $drow = $this->rdb->getRow($dsql, array(), DB_FETCHMODE_ASSOC);
        if (!$drow) {
            return false;
        }
        return $drow['dic_name'];
    }

    /*获得异常，取消订单原因  */
    function getRemark($orderId) {
        $remark_sql = "SELECT * FROM " . $this->table . ".`ecm_order_log` WHERE `changed_status` IN (0,30) AND `order_id`=" . $orderId;
        $remark = $this->rdb->getRow($remark_sql, array(), DB_FETCHMODE_ASSOC);
        if (!$remark) {
            return false;
        }
        return $remark['remark'];
    }
}
