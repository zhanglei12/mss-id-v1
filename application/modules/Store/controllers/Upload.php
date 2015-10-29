<?php 
class UpLoad{ 
	public $length; //限定文件大小 
	public $file; //判断此类是用于图片上传还是文件上传 
	public $fileName; //文件名 
	public $fileTemp; //上传临时文件 
	public $fileSize; //上传文件大小 
	public $error; //上传文件是否有错,php4没有 
	public $fileType; //上传文件类型 
	public $directory; // 
	public $maxLen; 
	public $errormsg; 
	function __construct($length,$file=true,$directory) 
	{ 
		$this->maxLen=$length; 
		$this->length=$length*1024; 
		$this->file=$file; //true为一般文件,false为图片的判断 
		$this->directory=$directory; 
	} 
	public function upLoadFile($fileField,$store_name) 
	{ 
		$this->fileName=$fileField['name']; 
		$this->fileTemp=$fileField['tmp_name']; 
		$this->error=$fileField['error']; 
		$this->fileType=$fileField['type']; 
		$this->fileSize=$fileField['size'];
		
		//$pathSign = DIRECTORY_SEPARATOR; 
		$path = $this->_isCreatedDir($this->directory);//取得路径 
	
		if($path)
		{ 
		
			$createFileType = $this->_getFileType($this->fileName);//设置文件类别 
			
			if($this->_isImg($fileField['type']!='1'))
			{
				die(json_encode(array("state"=>"-1","message"=>"上传图片类型错误")));
			}
			
			/*
			if(!$this->_isBig($length,$this->fileSize))
			{
				die(json_encode(array("state"=>"-2","message"=>"上传图片大小错误")));
			}
			*/
			
			$createFileName=$store_name; 
			
			
			return @move_uploaded_file($this->fileTemp,$this->directory."/".$createFileName.".".$createFileType)?$createFileType:false; 
		} 
	
	} 
	public function _isBig($length,$fsize) //返回文件是否超过规定大小 
	{ 
		return $fsize>$length ? true : false; 
	} 
	public function _getFileType($fileName) //获得文件的后缀 
	{ 
		return end(explode(".",$fileName)); 
	} 
	public function _isImg($fileType) //上传图片类型是否允许 
	{ 
		$type=array("jpeg","gif","jpg","bmp","png"); 
		$fileType=strtolower($fileType); 
		$fileArray=explode("/",$fileType);
		$file_type=end($fileArray); 
		return in_array($file_type,$type);
	} 
	public function _isCreatedDir($path) //路径是否存在，不存在就创建 
	{ 
		if(!file_exists($path)) 
		{ 
			return @mkdir($path,0755)?true:false; //权限755// 
		} 
		else 
		{ 
			return true; 
		} 
	} 
	
} 
 
?> 