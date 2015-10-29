<?php
class ReportorderModel 
{
	var $_db;

	function __construct($_db)
	{
		$this->_db=$_db;
	}
	/*送餐员结账单张订单的应收金额
	 * order_amount 数据库应收
	 * payment_name 结算方式
	 * */
	function getOrderAmount($arr){
		$order_amount=$arr['order_amount'];
		if($arr['payment_name']=="在线支付"){
			$order_amount=$arr['order_amount']-$arr['orignalorder_amount'];
		}
		if($arr['mss_payment_name']=="POS付款"){
			$order_amount=0;
		}
		return $order_amount;
	}
	/*送餐员结账单张订单的实收金额
	 * order_amount 数据库应收
	* payment_name 结算方式
	* */
	function getActualReceipt($arr){
		$actual_receipt=$arr['actual_receipt'];
		if($arr['mss_payment_name']=="POS付款"){
			$actual_receipt=0;
		}
		return $actual_receipt;
	}
	/*送餐员结账时单张订单应付金额 
	 * 
	 * */
	function getBuyAmount($arr){
		$sql="select balance from ecm_store where store_id =".$arr['seller_id'];
		$re=$this->_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
		$buy_amount=0;
		if($re['balance']==3){
			$buy_amount=$arr['buy_amount'];
		}
		return $buy_amount;
	}
	/* 送餐员结账单张订单实付金额
	 * 
	 *  */
	function getActualExpen($arr){
		$sql="select balance from ecm_store where store_id =".$arr['seller_id'];
		$re=$this->_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
		$buy_amount=0;
		if($re['balance']==3){
			$buy_amount=$arr['actual_expend'];
		}
		return $buy_amount;
	}
	function getEmpReasons($order_id,$type){
		$sql="select reason_value from ecm_order_reason where order_id=".$order_id." and reason_type=".$type;
		$reasons=$this->_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
		if (DB::isError($reasons))
		{
			return NULL;
		}
		return $reasons['reason_value'];
	}
	/*应付菜品总额   应收包装费*/
	function getGoodsAmount($order_id){
		$sql="select price,quantity,discount,packing_fee from ecm_order_goods where order_id=".$order_id;
		$info=$this->_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
		if (DB::isError($reasons))
		{
			return false;
		}
		$arr['goods_amount']=0;
		$arr['packing']=0;
		foreach($info as $v){
			$arr['goods_amount']+=$v['price']*$v['discount']*$v['quantity'];
			$arr['packing']+=$v['packing_fee'];
		}
		return $arr;
	}
	/*指定订单办理vip的金额  */
	function getVip($order_id){
		$sql='select * from ecm_membervip_log  m left join ecm_viptype v on m.viptype=v.id where order_id='.$order_id;
		$info=$this->_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
		if (DB::isError($reasons))
		{
			return false;
		}
		$vip_payment=0;
		foreach($info as $v){
			$vip_payment+=$v['price'];
		}
		return $vip_payment;
		
	}
	function newUser($buyer){
		$sql="select count(*) as num  from ecm_order where buyer_id=".$buyer;
		$count=$this->_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
		if (DB::isError($reasons))
		{
			return false;
		}
		if($count['num']>1){
			return "N";
		}else{
			return "Y";
		}
	}
	function orderRemark($order_id,$status=NULL){
		$sql="select * from ecm_order_log where order_id=".$order_id;
		if($status){
			$sql.=" and changed_status=".$status;
			$remarks=$this->_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
		}else{
		$remarks=$this->_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
		}
		if (DB::isError($reasons))
		{
			return false;
		}
		return $remarks;
	}
}
?>