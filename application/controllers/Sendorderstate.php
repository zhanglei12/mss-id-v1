<?php
class SendorderstateController extends Yaf_Controller_Abstract
{
	
	var $_httprequest;
	
	var $api_base;
	
	var $_orderpush;
	/**
	 * ณ๕สผปฏ
	 * @return None
	 */
	public function init()
	{
		$this->api_mail = Yaf_Registry::get('api_mail');
		$this->api_base = Yaf_Registry::get('api_base');
		$this->_httprequest = new PostMethod();
		
		global $LOG_MAIN_CONFIG;
		$LOG_MAIN_CONFIG['appenders']['default']['params']['file'] = '/data/log/web/soa.meishisong.cn/qiaojiangnan-%s.log';
		Logger::configure($LOG_MAIN_CONFIG);
		$log = Logger::getLogger('default');
		
		$file = array(
			APP_PATH.'/application/library/order_push/Orderpush.php',
		);
		
		yaf_load($file);
		
		$this->api_base = Yaf_Registry::get('api_base');
		
		$this->_orderpush = new Orderpush(array('log'=>$log));
	
	}
	
	public function indexAction()
	{
		 echo  $this->api_base->returnJson($this->_orderpush->index());
	
	}
	
	
}
?>