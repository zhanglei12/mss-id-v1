<?php
class OrderController extends Yaf_Controller_Abstract
{
	public function addAction()
	{
		$post_data = file_get_contents("php://input");
		$post_data = urldecode($post_data);
		$post_data = str_replace('\\','\\\\',$post_data);
		set_time_limit(0);
		if (empty($post_data)){
			$return['ret'] = "-4";
			$return['msg'] = "未得到可处理数据";
			echo json_encode($return);
			return;
		}
		$js_arr = json_decode($post_data,true);
		if ($partner_id = $js_arr['partner_id']) {
			switch ($partner_id){
				case '100000':
					$return['ret'] = "-2";
					$return['msg'] = "身份验证失败";
					echo json_encode($return);
					break;
				case '100001':
					
				case '100010':
					BaiDuController::dealInfo($post_data);
			}
		}else {
			$return['ret'] = "-2";
			$return['msg'] = "身份验证失败";
			echo json_encode($return);
			return;
		}
		//$this->api_log->info($params);
		/*if (empty($params))
		{
			throw new Exception("Error Params",API_ERR_PARAM);
		}
		if (empty($params['orderid']))
		{
			die(json_encode(array('state'=>-101,'message'=>'订单ID异常','data'=>'')));
		}
		if (empty($params['ordertime']))
		{
			die(json_encode(array('state'=>-102,'message'=>'订单时间异常','data'=>'')));
		}*/
		//$model = new OrderModel();
		//$res = $model->addOrder($params,$this->api_db);
		/*$this->api_mail->AddAddress('jing.luo@meishisong.cn');
		$this->api_mail->Subject = '添加订单成功';
		$this->api_mail->Body = '订单ID：'.$res;
		$this->api_mail->FromName = '美食送 发送至 jing.luo@meishisong.cn';
		$mailsend = $this->api_mail->Send();
		if(!$mailsend)
		{
			throw new Exception("Error Email",API_ERR_SENDMAIL);
		}
		$this->api_mail->ClearAddresses();
		$this->api_log->info('first test info');*/
		//$this->api_sms->send('18600181931','订单添加成功');
		//die(json_encode(array('state'=>1,'message'=>'添加成功','data'=>$res)));
	}
}
?>