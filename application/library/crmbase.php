<?php
/**
 * crm公用函数库
 * @author	hezhuang
 * @time 	20140716
 */

/*============================= 接口相关 =============================*/
/**
 * 客服登录
 * @param str	username	用户名
 * @param str	password	密码
 * @return array
 */
function customer_service_login($username, $password) {
	$url = API_URL."/api/api.php?app=user&act=logindispatcher&name=".$username."&password=".$password;
	return curl_cookie_file_get_contents($url);
}
/**
 * 测试新版登陆
 */
function emp_login($username, $password, $key){
	$url = SYSTEM_ORDER_API_URI."Login/login?username=".$username."&password=".$password."&key=".$key;
	return curl_file_get_contents($url);
}

/**
 * 获取快递区域
 * @return array
 */
function get_express_area() {
	$url = API_URL."/api/api.php?app=kuaidi&act=lists";
	return curl_file_get_contents($url);
}

/**
 * 获取订单详细信息
 * @param int	uid		接口用户ID
 * @param int	user_id	用户ID
 * @return array
 */
// function get_member_info_by_id($uid, $user_id, $key='') {
// 	$url = API_URL."/api/api.php?app=ordernew&act=getmemberinfobyid&uid=".$uid."&id=".$user_id."&key=".$key;
// 	return curl_file_get_contents($url);
// }
function get_member_info_by_id($uid,$user_id,$key) {
	$url = SYSTEM_ORDER_API_URI."Getorderinfo/getMember?uid=".$uid."&user_id=".$user_id."&key=".$key;
	return curl_file_get_contents($url);
}

/**
 * 收货人信息内 收货人地址信息
 * @param int	uid		接口用户ID
 * @param int	user_id	用户ID
 * @return array
 */
function get_consignee_address($uid,$user_id,$key) {
	$url = SYSTEM_ORDER_API_URI."Getorderinfo/getMemberAddress?uid=".$uid."&user_id=".$user_id."&key=".$key;
	return curl_file_get_contents($url);
}

/**
 * 检查区域是否超区
 * @param int	address	地址
 * @param int	city	城市
 * @param int	sign	签名验证
 * @return array
 */
function check_super_area($address,$city,$partner_id,$sign) {
	$url = SYSTEM_ORDER_API_URI."Getregion/getByAddress?address=".$address."&city=".$city."&partner_id=".$partner_id."&sign=".$sign;
	return curl_file_get_contents($url);
}

/**
 * 收货人信息内 根据用户id获取收货人历史信息
 * @param int	uid		接口用户ID
 * @param int	user_id	用户ID
 * @return array
 */
function get_consignee_history($uid,$user_id,$key,$pi,$pc) {
	$url = SYSTEM_ORDER_API_URI."Getorderinfo/getOrederHistory?uid=".$uid."&user_id=".$user_id."&key=".$key."&pi=".$pi."&pc=".$pc;
	return curl_file_get_contents($url);
}

/**
 * 订单详情内 根据order_id获取订单历史信息
 * @param int	uid		接口用户ID
 * @param int	order_id	订单ID
 * @return array
 */
function get_order_history($uid,$order_id,$key,$pi,$pc) {
	$url = SYSTEM_ORDER_API_URI."Getorderinfo/getOrederHistory?uid=".$uid."&order_id=".$order_id."&key=".$key."&pi=".$pi."&pc=".$pc;
	return curl_file_get_contents($url);
}

/**
 * 订单详情内 根据order_id获取收订单操作历史信息
 * @param int	uid		接口用户ID
 * @param int	order_id	订单ID
 * @return array
 */
function get_order_operate($uid,$order_id,$key) {
	$url = SYSTEM_ORDER_API_URI."Getorderinfo/getOperateOrder?uid=".$uid."&order_id=".$order_id."&key=".$key."&pi=".$pi."&pc=".$pc;
	return curl_file_get_contents($url);
}

/**
 * 获取历史订单
 * @param int	uid			接口用户ID
 * @param int	order_id	订单ID
 * @return array
 */
function get_history_order($uid, $order_id, $key='') {
	$url = API_URL."/api/api.php?app=ordernew&act=GetOrderHis&uid=".$uid."&orderid=".$order_id."&key=".$key;
	return curl_file_get_contents($url);
}

/**
 * 获取订单详细信息
 * @param int	uid			接口用户ID
 * @param int	order_id	订单ID
 * @return array
 */
function get_order_info($uid, $order_id, $key='') {
	$url = API_URL."/api/api.php?app=ordernew&act=orderinfo&uid=".$uid."&id=".$order_id."&key=".$key;
	return curl_file_get_contents($url);
}

/**
 * 修改订单状态（取消/关闭）
 * @param int	uid			接口用户ID
 * @param int	order_id	订单ID
 * @param int	status		订单状态
 * @param array	data		POST传参数组
 * @return （接口返回 -1:失败,-2:订单已修改,1:成功,2:log未插入成功）
 */
// function update_order_status($uid, $order_id, $status, $key='', $data='') {
// 	$url = API_URL."/api/api.php?app=ordernew&act=curious_order&uid=".$uid."&id=".$order_id."&status_value=".$status."&key=".$key;
// 	if(is_array($data))
// 		$data = http_build_query($data);
// 	return curl_post_file_get_contents($url, $data);
// }
function update_order_status($uid, $order_id, $status, $key='', $remark) {
	$url = SYSTEM_ORDER_API_URI."Getorderinfo/updateOrderStatus?uid=".$uid."&order_id=".$order_id."&request_status=".$status."&remark=".$remark;
	// $url = API_URL."/api/api.php?app=ordernew&act=curious_order&uid=".$uid."&id=".$order_id."&status_value=".$status."&key=".$key;
	return curl_post_file_get_contents($url);
}
/**
 * 返回订单关闭原因
 * @return 
 */
function get_order_close_reason() {
	$url = API_URL."/api/api.php?app=ordernew&act=getOrderCloseReason";
	return curl_file_get_contents($url);
}

/**
 * 快递员信息查询
 * @return 
 */
// function get_emp_search($order_id='', $emp_name='', $emp_mobile='', $s='', $region='', $key='',$region_id='') {
// 	$url = API_URL."/api/api.php?app=kuaidi&act=empsearch&order_id=".$order_id."&emp_name=".$emp_name."&emp_mobile=".$emp_mobile."&s=".$s."&region=".$region."&key=".$key."&region_id=".$region_id;
// 	//echo $url;
// 	return curl_file_get_contents($url);
// }
function get_emp_search($order_id='', $emp_name='', $emp_mobile='', $s='', $region='', $key='',$region_id='') {
	$url = SYSTEM_ORDER_API_URI."getorderinfo/empSearch?order_id=".$order_id."&emp_name=".$emp_name."&emp_mobile=".$emp_mobile."&s=".$s."&region=".$region."&key=".$key."&region_id=".$region_id;
	//echo $url;
	return curl_file_get_contents($url);
}

/**
 * 快递员改派
 * @param int	order_id	订单ID
 * @param int	emp_id		配送员ID
 * @param int	uid			调度员ID
 * @param int	lgs_id		物流ID
 * @return 
 */
// function emp_reassign($order_id='', $emp_id='', $uid='', $lgs_id='', $key) {
// 	$url = API_URL."/api/api.php?app=ordernew&act=edit_emporder&id=".$order_id."&emp_id=".$emp_id."&uid=".$uid."&lgs_id=".$lgs_id."&key=".$key;
// 	return curl_file_get_contents($url);
// }

 function emp_reassign($order_id='', $emp_id='', $uid='', $lgs_id='', $key) {
	$url = SYSTEM_ORDER_API_URI."getorderinfo/distribution?&id=".$order_id."&emp_id=".$emp_id."&uid=".$uid."&lgs_id=".$lgs_id."&key=".$key;
	return curl_file_get_contents($url); 
}


/**
 * 快递员批量改派
 * @param int	order_id	订单ID
 * @param int	emp_id		配送员ID
 * @param int	uid			调度员ID
 * @param int	lgs_id		物流ID
 * @return 
 */
// function emp_batch_reassign($orderArr='', $emp_id='', $uid='', $lgs_id='', $key) {
// 	$url = API_URL."/api/api.php?app=ordernew&act=emporder&ids="."$orderArr"."&empid=".$emp_id."&uid=".$uid."&lgs_id=".$lgs_id."&key=".$key;
// 	return curl_file_get_contents($url);
// }

function emp_batch_reassign($orderArr='', $emp_id='', $uid='', $lgs_id='', $key) {
	$url = SYSTEM_ORDER_API_URI."getorderinfo/distributionOrders?&ids=".$orderArr."&empid=".$emp_id."&uid=".$uid."&lgs_id=".$lgs_id."&key=".$key;
	return curl_file_get_contents($url);
} 
/**
 * curl 方式访问 url
 * @param str	url	网址
 * @return array
 */
function curl_file_get_contents($url) {
	// 初始化一个 cURL 对象
	$curl = curl_init();
	// 设置你需要抓取的URL
	curl_setopt($curl, CURLOPT_URL, $url);
	// 设置header
	curl_setopt($curl, CURLOPT_HEADER, 0);
	// 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_COOKIEFILE, $_SESSION['cookieFile']);
	curl_setopt($curl, CURLOPT_COOKIEJAR, $_SESSION['cookieFile']);
	$result = curl_exec($curl);
	$result = json_decode($result, true);
	return $result;
}

/**
 * curl 方式访问 url，POST
 * @param str	url		网址
 * @param str	data	POST数据
 * @return array
 */
function curl_post_file_get_contents($url, $data='') {
	$ch = curl_init();//初始化curl
	curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
	curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
	curl_setopt($ch, CURLOPT_COOKIEFILE, $_SESSION['cookieFile']);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $_SESSION['cookieFile']);
	curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$result = curl_exec($ch);//运行curl
	$result = json_decode($result, true);
	return $result;
}

/**
 * curl 方式访问 url，session
 * @param str	url	网址
 * @return array
 */
function curl_cookie_file_get_contents($url) {
	//创建一个用于存放cookie信息的临时文件
	$cookieFile = tempnam('.', '~');
	// 初始化一个 cURL 对象
	$curl = curl_init();
	// 设置你需要抓取的URL
	curl_setopt($curl, CURLOPT_URL, $url);
	// 设置header
	curl_setopt($curl, CURLOPT_HEADER, 0);
	// 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);
	$_SESSION['cookieFile'] = $cookieFile;
	// 运行cURL，请求网页
	$data = curl_exec($curl);
	// 关闭URL请求
	// curl_close($curl);
	// preg_match("/PHPSESSID=(.*?)(?:;|\r\n)/", $data, $matches);
	// echo $matches[1];
	// preg_match("/\{.+/", $data, $matches);
	// $val = json_decode($matches[0], true);
	$result = json_decode($data, true);
	return $result;
}
/**
 * 餐厅确认
 */
function res_confirm($order_id)
{
	$url = API_URL."/api/api.php?app=ordernew&act=d_restaurant_confirm&order_id=".$order_id;
	return curl_post_file_get_contents($url, $data);
}

/**
 *确认订单接口
 *
 */
function confirm_order($uid, $order_id, $management_fee, $shipping_fee, $status, $data='')
{
	$url = API_URL."/api/api.php?app=ordernew&act=confirm_orderex&uid=".$uid."&order_id=".$order_id."&status_value=".$status."&management_fee=".$management_fee."&shipping_fee=".$shipping_fee;
	// if(is_array($data))
		// $data = http_build_query($data);
	// echo $url;
	return curl_post_file_get_contents($url, $data);
}
/**
 *批量确认订单接口
 *
 */
function batch_confirm($uid,$order_id)
{
	$url = API_URL."/api/api.php?app=ordernew&act=batch_confirm&uid=".$uid."&ids=".$order_id;
	return curl_file_get_contents($url);
}
/**
 *保存订单菜品信息
 *
 */
function saveOrderGoods($order_id,$uid,$orderGoods)
{
	$url = SYSTEM_ORDER_API_URI."Getorderinfo/saveOrderGoods?order_id=".$order_id."&uid=".$uid;
	// $orderGoods = http_build_query($data,'flags_');
	// var_dump($data);
	return curl_post_file_get_contents($url, $orderGoods);
}
/*============================= 数组相关 =============================*/
/**
 * 函数joinKeyValue,把传入的数组重组为以原数组键名和键值分别以逗号相隔为值的新数组.
 * @param array	arr	一个数组
 * @return Array
 */
function joinKeyValue($arr)
{
	$keys	= '';
	$values = '';
	$newArr = array();
	foreach($arr as $k => $v)
	{
		$keys .= '`'.$k.'`,';
		$values .= "'".addslashes($v === NULL ? '' : $v)."',";
	}
	$newArr = array('keys'	=> trim($keys, ','),
					'vals'=> trim($values, ','));
	return $newArr;
}

/**
 * 函数joinKeyValue,把传入的数组重组为以原数组键名和键值相连为字符串.
 * @param array	arr	一个数组
 * @return Str
 */
function joinsKeyValue($arr)
{
	$joins = '';
	foreach($arr as $k => $v)
	{
		$joins .= '`'.$k.'` = '."'".addslashes(($v === NULL || $v == "" ) ? '' : $v)."',";
	}
	return trim($joins, ',');
}

/**
 * 替代json_encode
 */
function ecm_json_encode($value)
{	
    if (CHARSET == 'utf-8' && function_exists('json_encode')) 
    {
        return json_encode($value);
    }
	    $props = '';
	    if (is_object($value))
	    {
	        foreach (get_object_vars($value) as $name => $propValue)
	        {
	            if (isset($propValue))
	            {
	                $props .= $props ? ','.ecm_json_encode($name)  : ecm_json_encode($name);
	                $props .= ':' . ecm_json_encode($propValue);
	            }
	        }
	        return '{' . $props . '}';
	    }
	    elseif (is_array($value))
	    {
	        $keys = array_keys($value);
	        if (!empty($value) && !empty($value) && ($keys[0] != '0' || $keys != range(0, count($value)-1)))
	        {
	            foreach ($value as $key => $val)
	            {
	                $key = (string) $key;
	                $props .= $props ? ','.ecm_json_encode($key)  : ecm_json_encode($key);
	                $props .= ':' . ecm_json_encode($val);
	            }
	            return '{' . $props . '}';
	        }
	        else
	        {
	            $length = count($value);
	            for ($i = 0; $i < $length; $i++)
	            {
	                $props .= ($props != '') ? ','.ecm_json_encode($value[$i])  : ecm_json_encode($value[$i]);
	            }
	            return '[' . $props . ']';
	        }
	    }
	    elseif (is_string($value))
	    {
	        //$value = stripslashes($value);
	        $replace  = array('\\' => '\\\\', "\n" => '\n', "\t" => '\t', '/' => '\/',
	                        "\r" => '\r', "\b" => '\b', "\f" => '\f',
	                        '"' => '\"', chr(0x08) => '\b', chr(0x0C) => '\f'
	                        );
	        $value  = strtr($value, $replace);
	        if (CHARSET == 'big5' && $value{strlen($value)-1} == '\\')
	        {
	            $value  = substr($value,0,strlen($value)-1);
	        }
	        return '"' . $value . '"';
	    }
	    elseif (is_numeric($value))
	    {
	        return $value;
	    }
	    elseif (is_bool($value))
	    {
	        return $value ? 'true' : 'false';
	    }
	    elseif (empty($value))
	    {
	        return '""';
	    }
	    else
	    {
	        return $value;
	    }
	}
		// 二维数组转一位数组
if( ! function_exists('array_column'))
{
  function array_column($input, $columnKey, $indexKey = NULL)
  {
    $columnKeyIsNumber = (is_numeric($columnKey)) ? TRUE : FALSE;
    $indexKeyIsNull = (is_null($indexKey)) ? TRUE : FALSE;
    $indexKeyIsNumber = (is_numeric($indexKey)) ? TRUE : FALSE;
    $result = array();
 
    foreach ((array)$input AS $key => $row)
    {
      if ($columnKeyIsNumber)
      {
        $tmp = array_slice($row, $columnKey, 1);
        $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : NULL;
      }
      else
      {
        $tmp = isset($row[$columnKey]) ? $row[$columnKey] : NULL;
      }
      if ( ! $indexKeyIsNull)
      {
        if ($indexKeyIsNumber)
        {
          $key = array_slice($row, $indexKey, 1);
          $key = (is_array($key) && ! empty($key)) ? current($key) : NULL;
          $key = is_null($key) ? 0 : $key;
        }
        else
        {
          $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
        }
      }
 
      $result[$key] = $tmp;
    }
 
    return $result;
  }
}
	//ajax 获取区域
	// function ajaxGetAreaAction(){
	// 	$parent_id = $_POST['parent_id'];
	// 	$type = $_POST['type'];
	// 	$area = explode(",", $_POST['area_id']);
	// 	$parent_area = $this->order_model->getChildArea($parent_id);
	// 	if($parent_id == 0) {
	// 		foreach($parent_area as $k => $v) {
	// 			if(in_array($v['region_id'], $area) || $type == "neworder") {
	// 				$select = "selected";		
	// 			} else {
	// 				$select = '';
	// 			}
	// 			$uparea = $this->order_model->getUpArea($area[0]);
	// 			if($type != "neworder" && $uparea != $parent_id) {
	// 					$select = "selected";
	// 			}
	// 			$parentOptionStr .= "<option value={$v['region_id']}{$v['name']} {$select}>{$v['region_name']}</option>";
	// 		}
	// 		echo $parentOptionStr;
	// 		exit;
	// 	}
	// 	foreach($parent_area as $k => $v) {
	// 		if(in_array($v['region_id'], $area) || $type == "neworder") {
	// 			$select = "selected";		
	// 		} else {
	// 			$select = '';
	// 		}
	// 		$uparea = $this->order_model->getUpArea($area[0]);
	// 		if($type != "neworder" && $uparea != $parent_id) {
	// 				$select = "selected";
	// 		}
	// 		$parentOptionStr .= "<option value={$v['region_id']}{$v['name']} {$select}>{$v['region_name']}</option>";
	// 	}
	// 	echo $parentOptionStr;
	// }

?>