<?php
class TestModel extends BaseModel
{
	var $_db;
	var $_db_read;
	var $table  = 'soa_order'; /* 所映射的数据库表 */
	var $prikey = 'order_id';  /* 主键 */
	var $_name  = 'Demo';      /* 模型的名称 */
	
	function __construct()
	{
		parent::__construct();
		$this->_db = Yaf_Registry::get("api_db");
		$this->_db_read = Yaf_Registry::get("api_db_read");
		
	}
	/**
	 * 获取全部订单
	 * @param int		page	页码
	 * @param int		limit	条目数
	 * @param str		where	where条件
	 * @return array
	 */
	 public function getAllOrder($page,$limit,$where)
	 {
		try{
			$page = ($page-1)*$limit;
			$orderSql = "SELECT * FROM ech_order WHERE $where LIMIT $page,$limit"; 
			$orderArr = $this->_db_read->getAll($orderSql,array(),DB_FETCHMODE_ASSOC);
			return array('state'=>'1','message'=>'查询成功!','data'=>$orderArr);
		} catch(Exception $e){
			die(json_encode(array('state'=>"-2",'message'=>'数据异常','data'=>$e->getMessage())));
		}
	 }
	/**
	* 获取全部订单的订单个数
	* @param str		where	where条件
	* @return int
	*/
	public function getAllOrderSUM($where,$limit = '50')
	{
		$orderSql = "SELECT COUNT(order_id) count FROM ecm_order WHERE $where";
		$count_result = $this->_db_read->getRow($orderSql,array(),DB_FETCHMODE_ASSOC);
		if(!$count_result)
		{
			return array("state"=>"-1","message"=>"无店铺信息");
		}
		$page_model = new Page($count_result['count'],$limit);
		$order_summary_show = $page_model->fpage();
		return $order_summary_show;
	}
	 
	 
	
}