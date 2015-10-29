<?php
//处理上下架
class StateController extends Yaf_Controller_Abstract
{
	var $store_model;

	public function init()
	{
		$this->store_model = new StoreModel();
	}

	//批量上架操作
	public function  uploadAction()
	{

		$store_ids = $_POST['store_ids'];

		$to_partner_id = $_POST['to_partner_id'];

		if(!$store_ids || !$to_partner_id)
		{
			echo json_encode(array('state'=>'-1','message'=>'上架的参数缺失'));
			exit();
		}

		$store_ids = trim($store_ids,',');


		$sql = "select store_id,state from crm_store where store_id in(".$store_ids.") AND state=2";

		$result = $this->store_model->getAllInfo($sql);


		if(!empty($result)  &&  !is_array($result))
		{
			echo json_encode(array('state'=>'-2','message'=>'查询店铺上下架时失败'));
			exit();
		}


		if(!empty($result) && is_array($result))
		{

			$states = '';

			foreach($result as $store)
			{
				$states.=$store['store_id'].',';
			}

		}

		$states = trim($states,",");


		$update_data = array(
				'partner_store_state' => '1',
		);

		if($states)
		{
			$update_result = $this->store_model->update('crm_partner_store',$update_data,'source_partner_store_id in ('.$store_ids.') AND source_partner_store_id  not in ('.$states.') AND to_partner_id='.$to_partner_id);
		}else
		{
			$update_result = $this->store_model->update('crm_partner_store',$update_data,'source_partner_store_id in ('.$store_ids.') AND to_partner_id='.$to_partner_id);
		}	

		if(!$update_result)
		{
			echo json_encode(array('state'=>'-2','message'=>'修改状态时失败'));
			exit();
		}
			echo json_encode(array('state'=>'1','message'=>'修改状态成功'));			
	}


	//下架操作

	public  function store_off_loadAction()
	{
		$store_ids = $_POST['store_ids'];

		$to_partner_id = $_POST['to_partner_id'];

		if(!$store_ids || !$to_partner_id)
		{
			echo json_encode(array('state'=>'-1','message'=>'上架的参数缺失'));
			exit();
		}

		$store_ids = trim($store_ids,',');

		$update_data = array(
				'partner_store_state' => '2',
		);

		$update_result = $this->store_model->update('crm_partner_store',$update_data,'source_partner_store_id in ('.$store_ids.') AND to_partner_id='.$to_partner_id);

		if(!$update_result)
		{
			echo json_encode(array('state'=>'-2','message'=>'修改状态时失败'));
			exit();
		}
			echo json_encode(array('state'=>'1','message'=>'修改状态成功'));	
	
	}


}

?>