<?php
class Relation extends Yaf_Controller_Abstract
{
	var $api_db;
	var $api_db_crm;
	var $StoreMod;
	public function init()
	{	
		$this->api_db=Yaf_Registry::get("api_db");
		$this->api_db_crm=Yaf_Registry::get("api_db_crm");
	 	error_reporting(E_ERROR | E_PARSE);
		$file = array(
			LIB_PATH_PUBLIC.'/meishisong/Store.php'
		);
		yaf_load($file);
		$this->StoreMod=new Mss_Store(array('wdb'=>$this->api_db,'rdb'=>$this->api_db));
	}
	
	function getCity($region_id){
		$region=$region_id;
		
		for($i=0;;$i++){
			$sql='select * from ecm_region where region_id='.$region;
			$regionInfo=$this->api_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
			 if($regionInfo['parent_id']<1){
				return $regionInfo['region_name'];
				break;
			}
			if($i>6){
				return false;
				break;
			} 
			$region=$regionInfo['parent_id'];
		}
		return false;
	}
}
?>