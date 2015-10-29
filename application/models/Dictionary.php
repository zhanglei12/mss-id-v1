<?php
class DictionaryModel
{
	var $_db;

	function __construct($_db)
	{
		$this->_db=$_db;
	}
	/**
	 * @desc  根据数据字典类型返回数据字典数据
	 * @param int $type @desc 类型数值 1、异常类型;2、111;3、sss;4、送餐员迟到原因;5、退餐原因;6、付款差异原因,7,收款差异原因
	 * @return Array
	 */
	function GetErrors($type){
		if (empty($type))
		{
			$reasons = array();
		}else {
			$sql = "SELECT dic_value,dic_name FROM ecm_dictionary WHERE dic_type=".$type." AND dic_status=1 ORDER BY dic_orderno ASC";
			$reasons=$this->_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
			if (DB::isError($reasons))
			{
				return array();
			}
		}
		return array_filter($reasons);
	}
}
?>