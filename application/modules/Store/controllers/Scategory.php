<?php
class ScategoryController extends Yaf_Controller_Abstract
{
	var $scategory_model;

	public function init()
	{	

		session_start();
        header("Content-type: text/html; charset=utf-8");
        
		$role_array = array(7,8); //基础数据
		if(!isset($_SESSION['username']) || !in_array($_SESSION['role'],$role_array))
		{
			header("Location: ".WEB_PATH."/member/member/roleLogin");
		}
		

		$this->getView()->assign("app_path",APP_PATH);

		$this->scategory_model = new ScategoryModel();
	}

	public function indexAction()
	{
		try
		{

			$this->getView()->display("scategory/index");	

		}catch(Exception $e)
		{    
			die(json_encode(array('state'=>"-1",'message'=>'数据异常','data'=>$e->getMessage())));
		}  
    }


    public function showAction()
    {
    	$weixin = $_POST['weixin'];

    	if($weixin=='false')
    	{
    		$weixin ='0';
    	}else
    	{
    		$weixin = '1';
    	}

    	$scategory_array = $this->scategory_model->select($weixin);

    	if(!is_array($scategory_array))
    	{
    		echo json_encode(array('state'=>'-1','message'=>'获取店铺分类失败'));
    		exit();
    	}

    	echo json_encode(array('state'=>'1','message'=>'获取店铺分类成功','data'=>$scategory_array));
    }
	
	public function scategoryAction()
	{


		$scategory_result = $this->scategory_model->getChildren();
		if(!$scategory_result)
		{
			echo json_encode(array('state'=>'-1','message'=>'获取分类失败'));
			exit();
		}

			echo json_encode(array('state'=>'1','message'=>'获取分类成功','data'=>$scategory_result));
	}

	//根分类的添加

	public function parent_addAction()
	{
		$cate_name = $_POST['cate_name'];

		$weixin   =  $_POST['weixin'];

		if(!$cate_name || $weixin == null)
		{
			echo json_encode(array('state'=>'-1','message'=>'添加根分类的参数缺失'));
			exit();
		}

		if($weixin != '1')
		{
			$weixin = '0';
		}

		//是否已存在

		$check_sql = "select * from crm_scategory where parent_id='0'  AND cate_name='".$cate_name."'";

		$check_result = $this->scategory_model->getInfo($check_sql);

		if($check_result['cate_id'])
		{
			echo json_encode(array('state'=>'-101','message'=>'分类已存在'));
			exit();
		}

		$insert_data = array(
			'cate_name'  => $cate_name,
			'weixin'	 => $weixin,
		);

		$insert_result = $this->scategory_model->insert($insert_data,'crm_scategory');

		if(!$insert_data)
		{
			echo json_encode(array('state'=>'-2','message'=>'添加根分类失败'));
			exit();
		}

			echo json_encode(array('state'=>'1','message'=>'添加分类成功','data'=>$insert_result));
	}



	//二级分类的添加
	public function  addAction()
	{
		$parent_id = $_POST['parent_id'];

		$cate_name = $_POST['cate_name'];

		$weixin   = $_POST['weixin'];


		if(!$parent_id || !$cate_name || $weixin==null)
		{
			echo json_encode(array('state'=>'-1','message'=>'添加分类的参数缺失'));
			exit();
		}

		//1.先验证名字唯一

		$check_name = $this->scategory_model->check_name($parent_id,$cate_name);

		if((!empty($check_name))  && (!is_array($check_name))) 
		{
			echo json_encode(array('state'=>'-2','message'=>'查询同级分类失败'));
			exit();
		}

		if($check_name['cate_id'])
		{
			echo json_encode(array('state'=>'-3','message'=>'分类名字已存在'));
			exit();
		}

		//2.写入分类

		$insert_data = array(
				'cate_name'  => $cate_name,
				'parent_id'	 => $parent_id,
				'weixin'	 => $weixin,
		);

		$insert_result  = $this->scategory_model->insert($insert_data,'crm_scategory');

		if(!$insert_result)
		{
			echo json_encode(array('state'=>'-4','message'=>'写入分类失败'));
			exit();
		}

		//3.处理显示与隐藏

		//最后一个级别(上架了采取处理)

		if($weixin=='1')
		{
			$this->up_and_down2Action($insert_result,$weixin);
			$this->up_and_down2Action($parent_id,$weixin);
		}

		echo json_encode(array('state'=>'1','message'=>'写入分类成功','data'=>$insert_result));
	}


	//编辑分类

	public  function editAction()
	{
		$cate_id = $_POST['cate_id'];

		$cate_name = $_POST['cate_name'];

		if(!$cate_id || !$cate_name)
		{
			echo json_encode(array('state'=>'-1','message'=>'获得编辑分类的参数缺失'));
			exit();
		}

		//检查分类是否已经存在

		$check_sql = "select * from crm_scategory where parent_id=(select parent_id from crm_scategory where cate_id=".$cate_id.")  AND cate_name='".$cate_name."'  AND cate_id !=".$cate_id;

		$check_result  = $this->scategory_model->getInfo($check_sql);

		if($check_result['cate_id'])
		{
			echo json_encode(array('state'=>'-2','message'=>'分类已存在'));
			exit();
		}

		$update_data = array(
				'cate_name' => $cate_name,
		);


		$update_result = $this->scategory_model->update('crm_scategory',$update_data ,'cate_id='.$cate_id);


		if(!$update_result)
		{
			echo json_encode(array('state'=>'-2','message'=>'修改分类失败'));
			exit();
		}

		echo json_encode(array('state'=>'1','message'=>'编辑分类成功'));

	}


	public function up_and_downAction()
	{
		//上下架操作

		$cate_ids = $_POST['cate_ids'];

		$weixin   = $_POST['weixin'];

		if(!$cate_ids  || $weixin == null)
		{
			echo json_encode(array('state'=>'-1','message'=>'上下架参数缺失'));

			exit();
		}

		if($weixin != '1')
		{
			$weixin = '0';
		}

		$cate_ids = trim($cate_ids,',');

		$update_data = array(
			'weixin'	=> $weixin,
		);


		$update_result  = $this->scategory_model->update('crm_scategory',$update_data,'cate_id in('.$cate_ids.')');


		if(!$update_result)
		{
			echo json_encode(array('state'=>'-2','message'=>'上下架操作失败'));
			exit();
		}

			echo json_encode(array('state'=>'1','message'=>'上下架成功'));
			
	}


	public function up_and_down2Action($cate_ids,$weixin)
	{
		//上下架操作


		if(!$cate_ids  || $weixin == null)
		{
			echo json_encode(array('state'=>'-1','message'=>'上下架参数缺失'));

			exit();
		}

		if($weixin != '1')
		{
			$weixin = '0';
		}

		$cate_ids = trim($cate_ids,',');

		$update_data = array(
			'weixin'	=> $weixin,
		);


		$update_result  = $this->scategory_model->update('crm_scategory',$update_data,'cate_id in('.$cate_ids.')');


		if(!$update_result)
		{
			echo json_encode(array('state'=>'-2','message'=>'上下架操作失败'));
			exit();
		}
			
	}
	
	
}
?>