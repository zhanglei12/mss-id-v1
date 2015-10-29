<?php
class StatusModel extends BaseModel
{
	var $table  = 'soa_order_status_change';
	var $prikey = 'id';
	var $_name  = 'statu';
	
	function __construct()
	{
		parent::__construct();
	}
	function insertInfo($data){
		return $this->insert($data);
	}
	function edit($data,$where){
		return $this->update($this->table, $data,$where);
	}
}
?>