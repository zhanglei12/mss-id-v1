<?php
class MemberModel extends BaseModel
{
	var $_db;
	var $_db_read;
	var $table  = 'soa.soa_member';
	var $prikey = 'user_id';
	var $_name  = 'member';
	
	function __construct()
	{
		parent::__construct();
		$this->_db = Yaf_Registry::get("api_db");
		$this->_db_read = Yaf_Registry::get("api_db_read");
	}
	/*根据openid获得管理的店铺*/
	function getStores($openid){
		if($openid){
			$msql='select s.store_id from '.$this->table.' m left join soa.soa_member_store s on m.user_id=s.member_id where m.openid ="'.$openid.'"';
			$storeinfos=$this->getAllInfo($msql);
			$storeids="";
			if(!empty($storeinfos)){
			foreach($storeinfos as $storeid){
					$storeids.=",".$storeid['store_id'];
				}
				return trim($storeids,',');
				}
			}else{
				return false;
				}
		}
	/*判断是否为会员*/
		function ifMember($openId){
			if(!$openId){return false;}
			$sql='SELECT * FROM '.$this->table.' WHERE user_check=2 and openid="'.$openId.'"';
			$info=$this->getInfo($sql);
			if($info){
				return $info;
			}else{
				return false;
			}
		}
	function insertInfo($data){
		return $this->insert($data);
	}
	function edit($data,$where){
		return $this->update($this->table, $data,$where);
	}
	/*验证用户手机正确性*/
	function check_phone($user_mobile){
		$exp = "/^13[0-9]{1}[0-9]{8}$|15[012356789]{1}[0-9]{8}$|18[012356789]{1}[0-9]{8}$|14[57]{1}[0-9]$/";
		if(preg_match($exp,$user_mobile)){
			return "1";
		}else{
			return "-1";
		}
	} 

	/**
	 * 获取员工信息
	 * @param int		emp_no	员工编号
	 * @return array
	 */
	public function ecm_employee_info($emp_no) {
		$result = array();
		if(isset($emp_no)) {
			$empSql = "SELECT * FROM ecm_employee WHERE emp_no = '".$emp_no."' LIMIT 1";
			$result = $this->_db_read->getRow($empSql, array(), DB_FETCHMODE_ASSOC);
		}
		return $result;
	}
	/**
	 *查询员工在职状态的信息;
	*/
	
	public  function  ecm_employee_like($emp_name,$emp_status)
	{	
		$emp_name=trim($emp_name);
		
		if($emp_status!="")
		{	
			$sql="SELECT  emp_name  FROM  ecm_employee WHERE emp_name LIKE  '%{$emp_name}%' AND  emp_status=1 LIMIT 0,10 ";
			$res = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
			foreach($res  as  $k=>$v)
			{		
				$string.=$v['emp_name'].",";
			}
			$string=rtrim($string,",");
			$arr=explode(",", $string);
			return  $arr;
			

		}

		if($emp_status=="")
		{	
			$sql="SELECT  emp_name  FROM  ecm_employee WHERE emp_name LIKE  '%{$emp_name}%'  LIMIT 0,10 ";
			$res = $this->_db_read->getAll($sql, array(), DB_FETCHMODE_ASSOC);
			foreach($res  as  $k=>$v)
			{		
			$string.=$v['emp_name'].",";
			}
			$string=rtrim($string,",");
			$arr=explode(",", $string);
			return  $arr;
			
		}	
		
	}
	/**
	 * 更新员工登陆信息
	 * @param int		emp_id	员工id
	 * @return int
	 */
	public function update_emp_logion($emp_id,$key){
		try {
			$time = time();
			$sql = "UPDATE ecm_employee SET ticket = '$key', last_logindate = '$time' WHERE emp_id = '$emp_id'";
			$result = $this->_db->query($sql);
			return $result;
		} catch (Exception $e) {
			return null;
		}
		
	}
	
}
?>