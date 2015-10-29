<?php
class MatchModel extends BaseModel
{
	
	function __construct()
	{
		parent::__construct();
		$this->api_db_crm = Yaf_Registry::get('api_db_crm');
	}

	public function index($store_id)
	{
		//通过mss的store_id 返回crm的store_id

		$sql = 'select store_id,source_store_id from ecm_store where store_id ='.$store_id;
		
		$result = $this->api_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);

		if(empty($result)  || !is_array($result))
		{
			echo json_encode(array('state'=>'-1','message'=>'未查询到mss的店铺id'));
			exit();
		}

		if($result['store_id'] && !$result['source_store_id'])
		{
			//没有在新系统的情况下
			echo json_encode(array('state'=>'-1','message'=>'这个店铺没有推送到crm'));
			exit();
		}

			return  $result['source_store_id'];
	}

	public  function mss($store_id)
	{
		//通过crm的store_id 返回mss的store_id

		$sql ='select store_id,source_store_id from ecm_store where source_store_id='.$store_id;

		$result = $this->api_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);

		if(empty($result['store_id']))
		{
			return array('state'=>'-2','message'=>'当前店铺没有推送到mss');
		}else if($result['store_id'])
		{
			return array('state'=>'1','message'=>'查询成功','mss_store_id'=>$result['store_id']);
		}
	}
	
}
?>