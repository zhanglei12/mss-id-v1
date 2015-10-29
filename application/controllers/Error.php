<?php
class ErrorController extends Yaf_Controller_Abstract
{
	public function errorAction($exception)
	{
		switch($exception->getCode())
		{
			case YAF_ERR_LOADFAILD:
			case YAF_ERR_LOADFAILD_MODULE:
			case YAF_ERR_LOADFAILD_CONTROLLER:
			case YAF_ERR_LOADFAILD_ACTION:
				die(json_encode(array('state'=>-1,'message'=>'系统加载异常',data=>$exception->getMessage())));
			case API_ERR_DB:	
				die(json_encode(array('state'=>-12,'message'=>'数据库异常',data=>$exception->getMessage())));
			case API_ERR_FILE:	
				die(json_encode(array('state'=>-13,'message'=>'文件异常',data=>$exception->getMessage())));
			case API_ERR_URI:	
				die(json_encode(array('state'=>-14,'message'=>'URI异常',data=>$exception->getMessage())));
			case API_ERR_PARAM:	
				die(json_encode(array('state'=>-15,'message'=>'参数异常',data=>$exception->getMessage())));
			case API_ERR_SENDMAIL:	
				die(json_encode(array('state'=>-16,'message'=>'邮件异常',data=>$exception->getMessage())));
			case API_ERR_LOG:	
				die(json_encode(array('state'=>-17,'message'=>'日志异常',data=>$exception->getMessage())));
			case API_ERR_SMS:	
				die(json_encode(array('state'=>-18,'message'=>'短信异常',data=>$exception->getMessage())));
			case API_ERR_AUTH:
				die(json_encode(array('state'=>-19,'message'=>'授权异常',data=>$exception->getMessage())));
			case API_ERR_NET:
				die(json_encode(array('state'=>-20,'message'=>'网络访问失败',data=>$exception->getMessage())));
			default:
				die(json_encode(array('state'=>-11,'message'=>'未知异常',data=>$exception->getMessage())));
		}
	}
}
?>
