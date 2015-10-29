<?php
class RegionController extends Yaf_Controller_Abstract
{
	var $region_model;

	public function init()
	{
		session_start();
        header("Content-type: text/html; charset=utf-8");
        
		$role_array = array(7,8); //基础数 据
		if(!isset($_SESSION['username']) || !in_array($_SESSION['role'],$role_array))
		{
			header("Location: ".WEB_PATH."/member/member/roleLogin");
		}
		
		$this->region_model = new RegionModel();

		$this->getView()->assign('app_path', APP_PATH);
	}
	/*
	 * 区域操作
	*/
	public function indexAction()
	{
		try
		{
			$this->getView()->display("region/index");	
		}catch(Exception $e)
		{    
			die(json_encode(array('state'=>"-1",'message'=>'数据异常','data'=>$e->getMessage())));
		}  
	}

	// ajax加载区域信息
	public function regionAction()
	{ 

		$visibility  = $_POST['visibility'];

		if($visibility=='true')
		{
			$visibility='0';
		
		}else
		{
			$visibility = '1';
		}


		$region_array = $this->region_model->getTree($visibility);

		if(empty($region_array) ||  !is_array($region_array)) 
		{
			echo json_encode(array('state'=>'-1','message'=>'获取区域信息失败'));
		}else
		{
			echo json_encode(array('state'=>'1','message'=>'获取区域成功','data'=>$region_array));
		}
	}


	//修改区域名称

	public function editAction()
	{
		$region_id = $_POST['region_id'];

		$region_name = $_POST['region_name'];

		if(!$region_id|| !$region_name)
		{
			echo json_encode(array('state'=>'-1','message'=>'获取修改区域的信息失败'));
			exit();
		}


		//验证是否存在地区(同一个parent_id)


		$sql = "select * from crm_region where region_name='".$region_name."' AND parent_id=(select parent_id from crm_region where region_id=".$region_id.") AND region_id!=".$region_id;
	
		$return = $this->region_model->getInfo($sql);

		
		if(!empty($return) && $return['region_name'])
		{
			echo json_encode(array('state'=>'-2','message'=>'已有当前地区，请重新修改','region_name'=>$reset_region_name));

			exit();
		}

		//先修改名字

		$update_region_data = array(
			'region_name' => $region_name,
		);

		$region_name_update = $this->region_model->update('crm_region',$update_region_data,'region_id='.$region_id);


		if(!$region_name_update)
		{
			echo json_encode(array('state'=>'-3','message'=>'修改区域名字失败'));

			exit();
		}

		echo json_encode(array('state'=>'1','message'=>'修改区域成功'));
	
	}

	public function up_and_downAction()
	{
		$region_ids  = $_POST['region_ids'];
		$is_visibility = $_POST['is_visibility'];


		if(!$region_ids  || $is_visibility == null)
		{
			echo json_encode(array('state'=>'-1','message'=>'获取上下架的参数缺失'));
			exit();
		}

		$region_ids = trim($region_ids,',');

		if($is_visibility != '1')
		{
			$is_visibility = '0';
		}

		
		$update_data = array(
			'is_visibility'	=> $is_visibility,
		);


		$update_result  = $this->region_model->update('crm_region',$update_data,'region_id in('.$region_ids.')');


		if(!$update_result)
		{
			echo json_encode(array('state'=>'-2','message'=>'区域的隐藏或显示的操作失败'));
			exit();
		}

			echo json_encode(array('state'=>'1','message'=>'隐藏显示操作成功'));
	
	}


	//添加地区名称
	public function addAction()
	{

		$parent_id = $_POST['parent_id'];
		$region_name = $_POST['region_name'];
		$is_visibility  = $_POST['is_visibility'];

		if(!$parent_id || !$region_name || $is_visibility == null)
		{
			echo json_encode(array('state'=>'-1','message'=>'添加区域的参数缺失'));
			exit();
		}

		//验证是否存在地区
		$sql = "select * from crm_region where region_name='".$region_name."' and parent_id=".$parent_id;
		
		$return = $this->region_model->getInfo($sql);


		if(!empty($return) && $return['region_name'])
		{
			echo json_encode(array('state'=>'-2','message'=>'当前级别下已有相同地区，请重新输入'));
			exit();
		}

		$is_visibility = $_POST['is_visibility']?$_POST['is_visibility']:'0';

		$region_info = array(
				'region_name' => $region_name,
				'parent_id' => $parent_id,
				'is_visibility' => $is_visibility,
		);

		$region_result = $this->region_model->insert($region_info,'crm_region');

		

		if($region_result)
		{
			echo json_encode(array('state'=>'1','message'=>'success','data'=>$region_result));
			
		}else
		{
			echo json_encode(array('state'=>'-3','message'=>'添加区域失败'));
		}


	}	


	//添加一级区域

	public function parent_addAction()
	{
		$region_name = $_POST['region_name'];

		$is_visibility = $_POST['is_visibility'];

		if(!$region_name || $is_visibility == null)
		{
			echo json_encode(array('state'=>'-1','message'=>'获取添加区域的参数缺失'));
			exit();
		}

		if($is_visibility !=1)
		{
			$is_visibility  = '0';
		}

		//1.先判断名字是否已有区域存在

		$sql = "select * from crm_region where parent_id='0'  AND region_name='".$region_name."'";

		$result = $this->region_model->getInfo($sql);

		if($result['region_id'])
		{
			echo json_encode(array('state'=>'-101','message'=>'区域名字已经存在'));
			exit();
		}

		$insert_data = array(
			'region_name' => $region_name,
			'is_visibility' => $is_visibility,
			'parent_id'		=> '0',
		);

		$region_id  = $this->region_model->insert($insert_data,'crm_region');

		if(!$region_id)
		{
			echo json_encode(array('state'=>'-2','message'=>'新建区域失败'));
			exit();
		}

		 	echo json_encode(array('state'=>'1','message'=>'新建区域成功','data'=>$region_id));
	}

	public function zoneAction()
	{
		$region_id = $_POST['region_id'];

		if(!$region_id)
		{
			echo json_encode(array('state'=>'-1','message'=>'获取区域id失败'));
			exit();
		}

		$zone_sql = "select region_id,region_name,substring(ploygongeo,10,length(ploygongeo)-11) as `ploygongeo` from crm_zone where region_id =".$region_id;

		$zone_result = $this->region_model->getInfo($zone_sql);

		if(!empty($zone_result) && !is_array($zone_result))
		{
			echo json_encode(array('state'=>'-2','message'=>'查询区域范围时出现错误'));
			exit();
		}

		if(empty($zone_result))
		{
			//暂且没有区域范围的问题
			echo json_encode(array('state'=>'-3','message'=>'当前区域还没有设置区域范围'));
			exit();
		}

		echo json_encode(array('state'=>'1','message'=>'获取区域范围成功','data'=>$zone_result));

	}	

	public function zone_addAction()
	{
		$region_id = $_POST['region_id'];
		$zone = $_POST['zone'];
		$region_name = $_POST['region_name'];

		if(!$region_id || !$zone || !$region_name)
		{
			echo json_encode(array('state'=>'-1','message'=>'新增区域范围参数缺失'));
			exit();
		}

	    //获取最上级区域的region_id和region_name

	    $sql = "select region_id,region_name from crm_region where region_id = (select parent_id from crm_region where region_id=(select parent_id from crm_region where region_id=".$region_id."))";

	    $result = $this->region_model->getInfo($sql);

	    if(!$result['region_id'])
	    {
	    	echo json_encode(array('state'=>'-2','message'=>'查询最上级区域时失败'));
	    	exit();
	    }

	    $insert_data = array(
	    		'region_id' => $region_id,
	    		'region_name' =>$region_name,
	    		'ploygongeo'=>"POLYGON((".$zone."))",
	    		'parent_region_id' => $result['region_id'],
	    		'parent_region_name' => $result['region_name'],
	    );

	    $insert_result = $this->region_model->insert($insert_data,'crm_zone');

	    if(!$insert_result)
	    {
	    	echo json_encode(array('state'=>'-3','message'=>'写入区域范围失败'));
	    	exit();
	    }

	    	echo json_encode(array('state'=>'1','message'=>'写入区域范围成功'));

	}


	public function zone_editAction()
	{
		$region_id = $_POST['region_id'];

		$zone= preg_replace('|[a-zA-Z/]+|','',$_POST['zone']);

		if(!$region_id  || !$zone)
		{
			echo json_encode(array('state'=>'-1','message'=>'修改区域范围参数缺失'));
			exit();
		}


		//先查看是否有这个区域的区域范围

		$region_exists = $this->region_model->getInfo("select * from crm_zone where region_id=".$region_id);

		if(empty($region_exists['region_id']))
		{
			//则为添加
			$region_array = $this->region_model->getInfo("select region_id,region_name from crm_region where region_id=".$region_id);

			if(!$region_array['region_id'])
			{
				echo json_encode(array('state'=>'-4','message'=>'查询区域信息时失败'));
				exit();
			}

			$sql = "select region_id,region_name from crm_region where region_id = (select parent_id from crm_region where region_id=(select parent_id from crm_region where region_id=".$region_id."))";

		    $result = $this->region_model->getInfo($sql);

		    if(!$result['region_id'])
		    {
		    	echo json_encode(array('state'=>'-5','message'=>'查询最上级区域时失败'));
		    	exit();
		    }

		    $insert_data = array(
		    		'region_id' => $region_array['region_id'],
		    		'region_name' =>$region_array['region_name'],
		    		'ploygongeo'=>"POLYGON((".$zone."))",
		    		'parent_region_id' => $result['region_id'],
		    		'parent_region_name' => $result['region_name'],
		    );

		    $insert_result = $this->region_model->insert($insert_data,'crm_zone');

		    if(!$insert_result)
		    {
		    	echo json_encode(array('state'=>'-3','message'=>'修改区域范围失败'));
		    	exit();
		    }

		    	echo json_encode(array('state'=>'1','message'=>'修改区域范围成功'));
		    	exit();
		}else if(!is_array($region_exists))
		{
			echo json_encode(array('state'=>'-3','message'=>'查询区域范围信息时失败'));
			exit();
		}

		//修改区域范围

		$update_array = array(
			'ploygongeo'=>"POLYGON((".$zone."))",
		);

		$update_result = $this->region_model->update('crm_zone',$update_array,'region_id='.$region_id);

		if(!$update_result)
		{
			echo json_encode(array('state'=>'-2','message'=>'修改区域范围失败'));
			exit();
		}
			echo json_encode(array('state'=>'1','message'=>'修改区域范围成功'));
	}	
	
	


}

?>
