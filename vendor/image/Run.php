<?php
set_time_limit(0);

include_once './SimpleImage.php';

#require_once('/usr/local/php/lib/php/log4php/Logger.php');
#require_once('/data/web/public/conf/partner_uri.php');
#require_once('/data/web/api.meishisong.cn/application/library/dazhongdianping/Dazhongdianping.php');
require_once '/usr/local/php/lib/php/DB.php';
require_once('/data/web/public/conf/db.php');
#require_once('/data/web/public/conf/log.php');

#error_reporting(E_ALL ^ E_NOTICE);

class Run {
    public $api_db;

    # 正式
    public $Root = '/data/web/crm.meishisong.cn';

    # 测试
    #public $Root = '/data/tweb/crm.meishisong.mobi';

    public function __construct() {
    }

    public function start($argv) {
        if (count($argv) < 4) {
            echo "使用参数：crmLicenseResize '生成图片长边' '生成图片格式'\n ";
            echo "eg: php Run.php crmLicenseResize 800 jpg \n";
        } else {
            #$LOG_MAIN_CONFIG['appenders']['default']['params']['file'] = '/data/log/bin/changePic-%s.log';
            #Logger::configure($LOG_MAIN_CONFIG);
            #$log = Logger::getLogger('default');

            $api_dsn = DATABASE_48_TYPE . "://" . DATABASE_48_USERNAME . ":" . DATABASE_48_PASSWORD . "@" . DATABASE_48_HOST . ":" . DATABASE_48_PORT . "/crm";
            #$api_dsn = "mysql://root:zaq12wsxcde3_mss-mysql#2014@localhost:3306/crm";

            #echo $api_dsn . "\n";
            $db = new DB;
            $this->api_db = $db->connect($api_dsn, false);
            #var_dump($this->api_db);

            if (DB::isError($this->api_db)) {
                exit("Db isError");
            }
            $this->api_db->query("SET NAMES utf8");


            if (empty($argv[4])) {
                $this->crmLicenseResize($argv[2], $argv[3]);
            } else {
                #print_r($api_dsn);
                // 生成图片命添加前缀，测试使用
                $this->crmLicenseResize($argv[2], $argv[3], $argv[4]);
            }
        }
    }

    /**
     * 生成图示路径
     */
    public function mkDest($filePath, $length, $ext = 'jpg', $flag = '') {
        $info = pathinfo($filePath);
        $dest = $info['dirname'] . '/' . $flag . $info['filename'] . '_' . $length . '.' . $ext;
        return $dest;
    }

    /**
     * 修改crm营业执照图片尺寸
     */
    public function crmLicenseResize($length, $ext = 'jpg', $flag = '') {

        $si = new SimpleImage();

        $sql = "select lobby_pic,franchise_pic,eat_pic,license_pic store_logo from crm_store 
					where lobby_pic is not null or lobby_pic != '' 
					or franchise_pic is not null or franchise_pic != ''
					or eat_pic is not null or eat_pic != ''
					or license_pic is not null or license_pic != '' 
					or store_logo is not null or store_logo != '' ";

        $res = $this->api_db->getAll($sql, array(), DB_FETCHMODE_ASSOC);

        if ($flag) {
            $flag = $flag . '_';
        }

        foreach ($res as $item) {

            foreach ($item as $file) {
                if (empty($file)) {
                    continue;
                }

                $filePath = $this->Root . $file;
                $dest = $this->mkDest($filePath, $length, $ext, $flag);

                $re = $si->resize($filePath, $dest, $length);

                // 图片长宽小于指定长边边长
                if ($re == -2) {
                    copy($filePath, $dest);
                }

                print "{$re} # {$filePath} @ {$dest} \n";
            }
        }


        #$path = '/data/tweb/crm.meishisong.mobi/public/img/store/store';
        #$length = 20;


        /*
        $list = scandir($path);
        $si = new SimpleImage();
        
        if($flag)
            $flag = $flag . '_';
        
        foreach($list as $dir)
        {
            if($dir == '.' || $dir == '..')
                continue;
            
            $dirPath = $path .'/'. $dir;
            
            $files = scandir($dirPath);
            print($dirPath);
            foreach($files as $f)
            {
                if($f == '.' || $f == '..')
                    continue;
                
                $filePath = $dirPath . '/' . $f;
                $info = pathinfo($filePath);
                $dest = $dirPath . '/' . $flag . $info['filename'] . '_' . $length . '.' . $ext;
                $re = $si->resize($filePath, $dest, $length);
                
                print "{$re} # {$filePath} \n" ;
            }
        }
        
        print "\n crmLicenseResize 完成！ \n";
        
        */


    }

}//:~

$r = new Run();
$r->start($argv);
	
	