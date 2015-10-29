<?php
/*
  * 自定义视图类接口,用来封装smarty共yaf全局使用
  * 必须实现接口定义的5个函数
  */
class Smarty_Adapter implements Yaf_View_Interface
{
    public $_smarty;
    private $_script_path;
    
    public function __construct($config)
    {
        require 'Smarty.class.php';
        $this->_smarty = new Smarty;
        $this->_smarty->setCompileDir($config['compile_dir']);//编译目录
        $this->_smarty->setCacheDir($config['cache_dir']);//缓存目录
        //根据不同的模块设置不同的模版路径
        //$template_dir = APP_PATH."/application/views/";
        $uri = $_SERVER['REQUEST_URI'];
        $arruri = explode('/',$uri);
        $template_dir = APP_PATH."/application/views/";
        if (count($arruri)>3)
        {
            $strModuleName = strtolower($arrRequest->module);
            $template_dir = APP_PATH.'/application/modules/'.ucfirst($arruri[1]).'/views';
            $this->_script_path = $template_dir;
            $this->setScriptPath($template_dir);
        }
        else
        {
            $this->_script_path = $template_dir;
            $this->setScriptPath($template_dir);
        }
        $this->_smarty->setTemplateDir($config['template_dir']);
       /* $dispatcher = Yaf_Dispatcher::getInstance();
        $arrRequest = $dispatcher->getRequest();
        print_r($arrRequest);
        if (empty($arrRequest->module)) {
            $this->_script_path = APP_PATH."/application/views/";
            $this->setScriptPath(APP_PATH."/application/views/");
        } else {
            $strModuleName = strtolower($arrRequest->module);
            $this->_script_path = APP_PATH.'/application/modules/'.$strModuleName.'/views';
            $this->setScriptPath(APP_PATH.'/application/modules/'.$strModuleName.'/views');
        }*/
    }
    
    //返回要显示的内容
    public function render( $view_name ,  $tpl_vars = NULL )//string
    {
        $view_path = $this->_script_path.'/'.$view_name.".phtml";
        $cache_id     = empty($tpl_vars['cache_id']) ? '' : $tpl_vars['cache_id'];
        $compile_id = empty($tpl_vars['compile_id']) ? '' : $tpl_vars['compile_id'];
        return $this->_smarty->fetch($view_path, $cache_id, $compile_id, false);//返回应该输出的内容,而不是显示
    }
    
    //显示模版
    public function display( $view_name, $tpl_vars = NULL )//boolean
    {
        $view_path = $this->_script_path.'/'.$view_name.".phtml";
        $cache_id     = empty($tpl_vars['cache_id']) ? '' : $tpl_vars['cache_id'];
        $compile_id = empty($tpl_vars['compile_id']) ? '' : $tpl_vars['compile_id'];
        echo $this->_smarty->fetch($view_path);
        //return $this->_smarty->display($view_path, $cache_id, $compile_id);
    }
    
    //模版赋值
    public function assign( $name, $value = NULL )//boolean
    {
        return $this->_smarty->assign($name, $value);
    }
    
    //设定脚本路径
    public function setScriptPath( $view_directory )//boolean
    {
        return $this->_smarty->setTemplateDir($view_directory);
    }
    
    //得到脚本路径
    public function getScriptPath()//string
    {
        return $this->_script_path;
    }
    
}
?>