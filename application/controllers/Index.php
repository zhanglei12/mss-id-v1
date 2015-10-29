<?php
class IndexController extends Yaf_Controller_Abstract 
{
  

   public function loginoutAction()
   {
      session_start();

      if(isset($_SESSION['username']))
      {
         session_unset();
         session_destroy();
      }

     $url =WEB_PATH."/member/member/roleLogin"; 
      
     echo "<script language='javascript'  type='text/javascript'>window.location.href='$url'</script>";  
   }

}
?>