<?php
class StoreecmModel extends BaseModel
{
	var $table  = 'ecm_store';
	var $prikey = 'store_id';
	var $_name  = 'store';
	var $api_db;
	
	public function __construct()
	{
		parent::__construct();
		// $this->api_db = Yaf_Registry::get('api_db');
		// $this->api_mail = Yaf_Registry::get('api_mail');
		// $this->api_log = Yaf_Registry::get('api_log');
		// $this->api_sms = Yaf_Registry::get('api_sms');
		// $this->api_base = Yaf_Registry::get('api_base');
	}

	
	/*
	 *	店铺首页--显示店铺信息
	*/
	function select($page,$limit,$where='')
	{
	   try{
			
			$page = ($page-1)*$limit;

			$sql = "select * from ".$this->table." ".$where." limit ".$page." ,".$limit;
			
			$store_array = $this->getAllInfo($sql);
			
			if(!$store_array)
			{
				return array('state'=>'-1','message'=>'查询店铺失败!','data'=>'store query failed');
				exit();
			}else
			{
				return array('state'=>'1','message'=>'查询成功!','data'=>$store_array);
			}
		}catch(Exception $e)
		{   
			die(json_encode(array('state'=>"-2",'message'=>'数据异常','data'=>$e->getMessage())));
		}
	}
  //更新上下架的状态
	  public  function   editState($store_id,$state){
       $sql="update ".$this->table." set state =".$state." where  store_id=".$store_id;
       $queryresult=$this->api_db->query($sql);
      if (DB::isError($queryresult))
		{   
			throw new Exception("Error DB",API_ERR_DB);
		}
       
    
       }






















	
}
?>