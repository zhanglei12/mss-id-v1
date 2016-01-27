<?php
/**
 * 订单搜索类
 * guoyang
 * @time 2015/03/09
 */
class _Search
{
	var $order_model;
	var $orderStatus;
	
	function __construct(){
		include_once(APP_PATH."/application/models/Order.php");
		$this->order_model = new OrderModel();
		$this->orderStatus = array(
			'0'=>'取消',
			'10'=>'已提交',
			'11'=>'已确认',
			'12'=>'已分配',
			'13'=>'再分配',
			'20'=>'已接受',
			'21'=>'拒绝',
			'22'=>'已取餐',
			'23'=>'已退回',
			'24'=>'已到达目的地',
			'30'=>'异常关闭',
			'31'=>'异常',
			'32'=>'食物损坏',
			'33'=>'收货人未在指定地址',
			'34'=>'餐厅打烊',
			'35'=>'食物售馨',
			'36'=>'其他',
			'37'=>'申请退款',
			'38'=>'同意退款',
			'39'=>'拒绝退款',
			'50'=>'已完成',
			'-100'=>'已修改'
		);
	}
	
	/**
	 * 搜索
	 * @param  array  搜索参数
	 * @return array  处理后的数据和拼装的SQL语句 
	 */
	function search($search) {
        $todate = date('Y-m-d');
        // Search-拼接SQL条件
        $searchSql = $search['searchSql'];
        $orderBy = " ORDER BY a.add_time DESC ";
        // Search-time | 要求送达时间 OR 下单时间
        $request_times = substr($search['request_times'], 0, 16);
        $request_timee = substr($search['request_timee'], 0, 16);
        $add_times = substr($search['add_times'], 0, 16);
        $add_timee = substr($search['add_timee'], 0, 16);
        
        $add_times = $add_times == null ? $todate . ' 00:00' : $add_times;
        $add_timee = $add_timee == null ? $todate . ' 23:59' : $add_timee;
        $data['add_times'] = $add_times;
        $data['add_timee'] = $add_timee;
        $add_times = strtotime($add_times.':00');
        $add_timee = strtotime($add_timee.':59');

        if ($request_times > $request_timee || $add_times > $add_timee) {
            exit("开始时间不能大于结束时间！");
        }
        if (($add_timee - $add_times) > 604800) {
            exit("开始时间与结束时间大于7天！");
        }
        if ($add_times) {
            $searchSql .= " AND a.add_time >= '" . $add_times . "' ";
        }
        if ($add_timee) {
            $searchSql .= " AND a.add_time <= '" . $add_timee . "' ";
        }
        // Search-判断是否搜索
        if ($search['search']) {
            // Search-订单编号
            if ($search['order_sn'] != '') {
                $order_sn = $search['order_sn'];
                $data['order_sn'] = $order_sn;
                $searchSql = " a.order_sn = '" . $order_sn . "' ";
            }
            // Search-合作伙伴订单编号
            if ($search['partner_order_id'] != '') {
                $partner_order_id = $search['partner_order_id'];
                $data['partner_order_id'] = $partner_order_id;
                $searchSql = " a.partner_order_id = '" . $partner_order_id . "' ";
            }
            // Search-要求送达时间
            if ($search['request_times'] != '') {
                $data['request_times'] = $request_times;
                $request_times .= ':00';
                $searchSql .= " AND b.request_time >= '" . strtotime($request_times) . "' ";
            }
			if ($search['request_timee'] != '') {
                $data['request_timee'] = $request_timee;
                $request_timee .= ':00';
                $searchSql .= " AND b.request_time <= '" . strtotime($request_timee) . "' ";
            }
            // Search-收货人用户名
            if ($search['consignee'] != '') {
                $consignee = $search['consignee'];
                $data['consignee'] = $consignee;
                $searchSql .= " AND (b.consignee = '" . $consignee . "' OR a.buyer_name = '" . $consignee . "') ";
            }
            // Search-收货人电话
            if ($search['phone_mob'] != '') {
                $phone_mob = $search['phone_mob'];
                $data['phone_mob'] = $phone_mob;
                $searchSql .= " AND b.phone_mob LIKE '%" . $phone_mob . "%' ";
            }
            // Search-店铺名称
            if ($search['seller_name'] != '') {
                $seller_name = $search['seller_name'];
                $data['seller_name'] = $seller_name;
                $searchSql .= " AND a.seller_name LIKE '%" . $seller_name . "%' ";
            }
            // Search-快递员
            if ($search['emp_name'] != '') {
                $emp_name = $search['emp_name'];
                $data['emp_name'] = $emp_name;
                $searchSql .= " AND a.emp_name = '" . $emp_name . "' ";
            }
            // Search-合作伙伴
            if ($search['partner'] != '' && $search['partner'][0] != 0) {
                if (!($search['selectAllpartner'][0] === '0')) {
                    $partner = $search['partner'];
                    foreach ($partner as $k => $v) {
                        if ($v == 100000) {
                            $partner[$k] = "''";
                            $partner[] = 0;
                        }
                    }
                    $selectpartner = implode(",", $partner);
                    $searchSql .= " AND a.from_partner in (" . $selectpartner . ") ";

                    $cooperate = $this->order_model->get_cooperate();
                    foreach ($cooperate as $k => $v) {
                        $partnerList[$v['appkey']] = $v['cooperate_name'];
                    }
                    foreach ($partnerList as $k => $v) {
                        if (in_array($k, $search['partner'])) {
                            $parentOptionStr .= "<option value={$k} selected>{$v}</option>";
                        }
                        else {
                            $parentOptionStr .= "<option value={$k}>{$v}</option>";
                        }
                    }
                    $data['partnerList'] = $parentOptionStr;
                    $data['selectpartner'] = $search['partner'];
                }
            }
            // Search-订单状态
            if (!($search['selectAllorderStatus'][0] === '0')) {
                $selectStatus = implode(",", $search['orderStatus']);
                $searchSql .= " AND a.status in (" . $selectStatus . ") ";
                foreach ($this->orderStatus as $k => $v) {
                    if (in_array($k, $search['orderStatus'])) {
                        $orderStatus .= "<option value={$k} selected>{$v}</option>";
                    }
                    else {
                        $orderStatus .= "<option value={$k}>{$v}</option>";
                    }
                }
                $data['selectStatus'] = $orderStatus;
            }
            // Search-区域
            if ($search['selectItemarea'] != '' && $search['selectItemarea'] != 0) {
                $area = implode(",", $search['selectItemarea']);
                if (!($search['parent_area'] == 0 && $search['selectAllarea'][0] === '0')) {
                    $searchSql .= " AND b.region_id in (" . $area . ") ";
                }
                $parent_area = $search['parent_area'];
                $data['area'] = $area;
                $data['parent_area'] = $parent_area;
            }
            // Search-支付方式
            if ($search['payment_name'] != '') {
                $payment_name = $search['payment_name'];
                $data['payment_name'] = $payment_name;
                $searchSql .= " AND a.payment_name = '" . $payment_name . "' ";
            }
            // Search--判断订单来源
            if ($search['csOrder']) {
                $csOrder = $search['csOrder'];
                $data['csOrder'] = $csOrder;
                $data['csOrderType'] = $csOrder;
                $searchSql .= " AND a.cs_order_type = '" . $csOrder . "' ";
            }
            // ORDER BY
            if ($search['orderBy'] != '') {
                $orderBy = $search['orderBy'];
                $orderBy = " ORDER BY a." . orderBy . "DESC ";
            }
        }
        $data['orderBy'] = $orderBy;
        $data['searchSql'] = $searchSql;
        return $data;
    }
}	 