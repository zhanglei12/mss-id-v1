<?php
class BaseModel
{
	var $api_db;
	var $api_db_read;
	var $api_mail;
	var $api_log;
	var $api_sms;
	var $api_base;
	/* 所映射的数据库表 */
    var $table = '';

    /* 主键 */
    var $prikey= '';

    /* 别名 */
    var $alias = '';

    /* 模型的名称 */
    var $_name   = '';

    /* 表前缀 */
    var $_prefix = '';

    /* 数据验证规则 */
    var $_autov = array();

	/**
	 * 初始化类，相当于构造函数
	 * @return none
	 */
	public function __construct()
	{
		$this->api_db = Yaf_Registry::get('api_db');
		$this->api_db_read = Yaf_Registry::get('api_db_read');
		$this->api_mail = Yaf_Registry::get('api_mail');
		$this->api_log = Yaf_Registry::get('api_log');
		$this->api_sms = Yaf_Registry::get('api_sms');
		$this->api_base = Yaf_Registry::get('api_base');
	}
		
	public function insert($info,$table="")
	{	
		$table=$table?$table:$this->table;
		return $this->api_base->insertData2DB($table,$info,$this->api_db,True);
	}
	function update($tbname, $array, $where = '')
	{
		if($where)
		{
			$tb_fields=$this->get_fields($tbname);
			$sql = '';
			foreach($array as $k=>$v)
			{
				if(in_array($k,$tb_fields))
				{
					$k=str_replace("'",'',$k);
					$sql .= ", `$k`='$v'";
				}
			}
			$sql = substr($sql, 1);
			if($sql)$sql = "update `$tbname` set $sql where $where";
			else return 0;
		}
		else
		{
			$sql = "replace into `$tbname`(`".implode('`,`', array_keys($array))."`) values('".implode("','", $array)."')";
		}
		
		return $resi = $this->api_db->query($sql);
		if (DB::isError($resi))
		{
			throw new Exception("Error DB",API_ERR_DB);
		}
		$resid = $this->get_last_id();
		return $resid;
	}
	function get_fields($table)
	{
		$fields=array();
		$result=$this->api_db->tableInfo($table);
		foreach($result as $val)
		{
			$fields[]=$val['name'];
		}
		return $fields;
	}
	function get_last_id(){
		$resid = $this->api_db_read->getOne("select last_insert_id()");
		if (DB::isError($resid))
		{
			throw new Exception("Error DB",API_ERR_DB);
		}
		return $resid;
	}
	/**
	 * 创建像这样的查询: "IN('a','b')";
	 *
	 * @access   public
	 * @param    mix      $item_list      列表数组或字符串,如果为字符串时,字符串只接受数字串
	 * @param    string   $field_name     字段名称
	 * @author   wj
	 *
	 * @return   void
	 */
	function db_create_in($item_list, $field_name = '')
	{
		if (empty($item_list))
		{
			return $field_name . " IN ('') ";
		}
		else
		{
			if (!is_array($item_list))
			{
				$item_list = explode(',', $item_list);
				foreach ($item_list as $k=>$v)
				{
					$item_list[$k] = intval($v);
				}
			}
	
			$item_list = array_unique($item_list);
			$item_list_tmp = '';
			foreach ($item_list AS $item)
			{
				if ($item !== '')
				{
					$item_list_tmp .= $item_list_tmp ? ",'$item'" : "'$item'";
				}
			}
			if (empty($item_list_tmp))
			{
				return $field_name . " IN ('') ";
			}
			else
			{
				return $field_name . ' IN (' . $item_list_tmp . ') ';
			}
		}
	}
	function getInfo($sql){
		return $this->api_db_read->getRow($sql,array(),DB_FETCHMODE_ASSOC);
	}
	function getAllInfo($sql){
		return $this->api_db_read->getAll($sql,array(),DB_FETCHMODE_ASSOC);
	}
	function delete($ids){
		$id=$this->db_create_in($ids);
		$sql='DELETE FROM '.$this->table.' WHERE '.$this->prikey.' in('.$id.')';
		$re=$this->api_db->query($sql);
		if (DB::isError($re))
		{
			throw new Exception("Error DB",API_ERR_DB);
		}
		return $re;
	}


	function delete_sql($sql)
	{
		$re=$this->api_db->query($sql);
		
		if (DB::isError($re))
		{
			throw new Exception("Error DB",API_ERR_DB);
		}
		return $re;
	}
}
?>