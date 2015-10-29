<?php
class Bootstrap extends Yaf_Bootstrap_Abstract
{
    public function _initDB()//
    {
		$api_dsn = DATABASE_48_TYPE."://".DATABASE_48_USERNAME.":".DATABASE_48_PASSWORD."@".DATABASE_48_HOST.":".DATABASE_48_PORT."/".DATABASE_48_NAME_NOWMSS;
        $api_dsn_crm = DATABASE_48_TYPE."://".DATABASE_48_USERNAME.":".DATABASE_48_PASSWORD."@".DATABASE_48_HOST.":".DATABASE_48_PORT."/".DATABASE_48_NAME;
        $api_dsn_lgs = DATABASE_48_TYPE."://".DATABASE_48_USERNAME.":".DATABASE_48_PASSWORD."@".DATABASE_48_HOST.":".DATABASE_48_PORT."/".DATABASE_48_NAME_LGS;
		$api_dsn_read = DATABASE_SLAVE_TYPE."://".DATABASE_SLAVE_USERNAME.":".DATABASE_SLAVE_PASSWORD."@".DATABASE_SLAVE_HOST.":".DATABASE_SLAVE_PORT."/".DATABASE_SLAVE_NAME_NOWMSS;
        $api_dsn_read_crm = DATABASE_SLAVE_TYPE."://".DATABASE_SLAVE_USERNAME.":".DATABASE_SLAVE_PASSWORD."@".DATABASE_SLAVE_HOST.":".DATABASE_SLAVE_PORT."/".DATABASE_SLAVE_NAME;
        $api_dsn_read_lgs = DATABASE_SLAVE_TYPE."://".DATABASE_SLAVE_USERNAME.":".DATABASE_SLAVE_PASSWORD."@".DATABASE_SLAVE_HOST.":".DATABASE_SLAVE_PORT."/".DATABASE_SLAVE_NAME_LGS;
        $db = new DB;
        $api_db = $db->connect($api_dsn,false);
		$api_db_crm = $db->connect($api_dsn_crm,false);
		$api_db_lgs = $db->connect($api_dsn_lgs,false);
		$api_db_read = $db->connect($api_dsn_read,false);
		$api_db_read_crm = $db->connect($api_dsn_read_crm,false);
		$api_db_read_lgs = $db->connect($api_dsn_read_lgs,false);
		if(DB::isError($api_db))
        {
        	$api_dsn = DATABASE_48_TYPE."://".DATABASE_48_USERNAME.":".DATABASE_48_PASSWORD."@".DATABASE_48_HOSTBACK.":".DATABASE_48_PORT."/".DATABASE_48_NAME_NOWMSS;
        	$db = new DB;
        	$api_db = $db->connect($api_dsn,false);
        	if(DB::isError($api_db))
        	{
           		die($api_db->getMessage());
        	}
        }
		if(DB::isError($api_db_crm))
        {
        	$api_dsn_crm = DATABASE_48_TYPE."://".DATABASE_48_USERNAME.":".DATABASE_48_PASSWORD."@".DATABASE_48_HOSTBACK.":".DATABASE_48_PORT."/".DATABASE_48_NAME;
        	$db = new DB;
        	$api_db_crm = $db->connect($api_dsn_crm,false);
        	if(DB::isError($api_db_crm))
        	{
           		die($api_db_crm->getMessage());
        	}
        }
		if(DB::isError($api_db_lgs))
        {
        	$api_dsn_lgs = DATABASE_48_TYPE."://".DATABASE_48_USERNAME.":".DATABASE_48_PASSWORD."@".DATABASE_48_HOSTBACK.":".DATABASE_48_PORT."/".DATABASE_48_NAME_LGS;
        	$db = new DB;
        	$api_db_lgs = $db->connect($api_dsn_lgs,false);
        	if(DB::isError($api_db_lgs))
        	{
           		die($api_db_lgs->getMessage());
        	}
        }
		if(DB::isError($api_db_read))
        {
        	$api_dsn_read = DATABASE_SLAVE_TYPE."://".DATABASE_SLAVE_USERNAME.":".DATABASE_SLAVE_PASSWORD."@".DATABASE_SLAVE_HOSTBACK.":".DATABASE_SLAVE_PORT."/".DATABASE_SLAVE_NAME_NOWMSS;
        	$db = new DB;
        	$api_db_read = $db->connect($api_dsn_read,false);
        	if(DB::isError($api_db_read))
        	{
           		die($api_db_read->getMessage());
        	}
        }
		if(DB::isError($api_db_read_crm))
        {
        	$api_dsn_read_crm = DATABASE_SLAVE_TYPE."://".DATABASE_SLAVE_USERNAME.":".DATABASE_SLAVE_PASSWORD."@".DATABASE_SLAVE_HOSTBACK.":".DATABASE_SLAVE_PORT."/".DATABASE_SLAVE_NAME;
        	$db = new DB;
        	$api_db_read_crm = $db->connect($api_dsn_read_crm,false);
        	if(DB::isError($api_db_read_crm))
        	{
           		die($api_db_read_crm->getMessage());
        	}
        }
		if(DB::isError($api_db_read_lgs))
        {
        	$api_dsn_read_lgs = DATABASE_SLAVE_TYPE."://".DATABASE_SLAVE_USERNAME.":".DATABASE_SLAVE_PASSWORD."@".DATABASE_SLAVE_HOSTBACK.":".DATABASE_SLAVE_PORT."/".DATABASE_SLAVE_NAME_LGS;
        	$db = new DB;
        	$api_db_read_lgs = $db->connect($api_dsn_read_lgs,false);
        	if(DB::isError($api_db_read_lgs))
        	{
           		die($api_db_read_lgs->getMessage());
        	}
        }
        $api_db->query("SET NAMES utf8");
        $api_db_read->query("SET NAMES utf8");
        Yaf_Registry::set('api_db',$api_db);
		Yaf_Registry::set('api_db_crm',$api_db_crm);
		Yaf_Registry::set('api_db_lgs',$api_db_lgs);
		Yaf_Registry::set('api_db_read',$api_db_read);
		Yaf_Registry::set('api_db_read_crm',$api_db_read_crm);
		Yaf_Registry::set('api_db_read_lgs',$api_db_read_lgs);
    }
    public function _initMail()
    {
        $api_mail = new PHPMailer();
        $api_mail->CharSet = MAIL_OA_CHARSET;
        $api_mail->Host = MAIL_OA_HOST;
        $api_mail->Port = MAIL_OA_PORT;
        $api_mail->SMTPAuth = MAIL_OA_SMTPAUTH;
        $api_mail->SMTPSecure = MAIL_OA_SMTPSECURE;
        $api_mail->Username = MAIL_OA_USERNAME;
        $api_mail->Password = MAIL_OA_PASSWORD;
        $api_mail->From = MAIL_OA_FROM;
        $api_mail->IsSmtp();
        $api_mail->IsHTML(true);
        Yaf_Registry::set('api_mail',$api_mail);
    }
    public function _initLog()
    {
        global $LOG_MAIN_CONFIG;
        Logger::configure($LOG_MAIN_CONFIG);
        $api_log = Logger::getLogger('default');
        Yaf_Registry::set('api_log',$api_log);
    }
    public function _initBase()
    {
        try
        {
            $api_base = new _QH_Base();
            Yaf_Registry::set('api_base',$api_base);
        }
        catch(Exception $exception)
        {
            die(json_encode(array('state'=>-108,'message'=>'base error',data=>$exception->getMessage())));
        }
    }
    public function _initSms()
    {
        $api_sms = new _QH_Sms();
        $api_sms->Uri = SMS_HL_URI;
        $api_sms->Username = SMS_HL_USERNAME;
        $api_sms->Password = SMS_HL_PASSWORD;
        $api_sms->Epid = SMS_HL_EPID;
        $api_sms->WebEncoding = SMS_HL_WEBENCODING;
        $api_sms->Encoding = SMS_Hl_ENCODING;
        Yaf_Registry::set('api_sms',$api_sms);
    }
   

    public function _initView(Yaf_Dispatcher $dispatcher)
    {
        $template_dir = APP_PATH."/application/views/";
        $uri = $_SERVER['REQUEST_URI'];
        $arruri = explode('/',$uri);
        $last = explode('.',$arruri[count($arruri)-1]);
        //echo count($last);
        //echo '<br>';
        if (count($arruri)>3)
            $template_dir = APP_PATH.'/application/modules/'.ucfirst($arruri[1]).'/views';
        //echo $template_dir.'<br>';
        //echo 'template_dir:'.$template_dir.'<br>';
        $config=array(
            "template_dir" => $template_dir,
            "compile_dir"  => APP_PATH."/cache/template_c/",
        );
        Yaf_Dispatcher::getInstance()->autoRender(false);
        //Yaf_Dispatcher::getInstance()->disableView();
        $view= new Smarty_Adapter(null,$config);

        Yaf_Dispatcher::getInstance()->setView($view);
    }
   
 
}
?>