<?php
/**
 * PHPExcel
 *
 * Copyright (C) 2006 - 2011 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2011 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.7.6, 2011-02-27
 */

/** Error reporting */
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

date_default_timezone_set('Europe/London');

/** PHPExcel_IOFactory */
require_once '../Classes/PHPExcel/IOFactory.php';


if (!file_exists("test.xls")) {
	exit("Please find test.xls first.\n");
}

//echo date('H:i:s') . " Load from Excel2007 file\n";
echo date('H:i:s') . " Load from Excel5 file\n";
$objReader = PHPExcel_IOFactory::createReader('Excel5');
$objPHPExcel = $objReader->load("test.xls");
header('Content-type:text/html;charset=UTF-8');
echo date('H:i:s') . " Iterate worksheets\n";
$i = 0 ;

 /**
  * bd_longitude  经度
  * bd_latitude 纬度
  * bd_name 建筑名
  * bd_alias1 别名1
  * bd_alias2 别名2
  * bd_alias3 别名3
  * bd_street 街道
  * bd_street 街道
  * region_id2 朝阳 -- 需要匹配
  * region_id3 酒仙桥 --需要匹配
  * 要和region表匹配地区名,得到地区ID
  * 
  * */
 
 $f_arr = array('bd_longitude' , 'bd_latitude' , 'bd_name' , 'bd_alias1' , 'bd_alias2' , 'bd_alias3' , 'bd_street' , 'region_id4' , 'region_id3' , 'region_id2');
 
 $need_search_arr = array('region_id4' , 'region_id3' , 'region_id2');
 
 $insert_values = $tmp_arr = array();
foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
	
	$i++;
	echo "$i<Br/>";
	if($i < 2)
	{
		continue;
	}else{
		//echo '- ' .  $worksheet->getTitle() . "<Br/>";
			foreach ($worksheet->getRowIterator() as $row) {
		//echo '    - Row number: ' . $row->getRowIndex() . "<Br/>";
					$row_id = $row->getRowIndex();
					if($row_id <= 1 )
					{
						continue;
					}
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
			
			foreach ($cellIterator as $k => $cell) {
				if (!is_null($cell) && $k > 0) {
					
					if(in_array( $f_arr[$k-1] ,$need_search_arr))
					{
							//echo $f_arr[$k] ."<Br/>";
							$insert_values[$row_id][] =  "'".  get_region_id($cell->getCalculatedValue())   ."'";
					}else{
						  
							$insert_values[$row_id][] =  "'". $cell->getCalculatedValue()  ."'";
					}
				
					
				//	echo '        - Cell: ' . $k . ' - '. $cell->getCoordinate() . ' - ' . $cell->getCalculatedValue() . "<Br/>";
				}
			}
			$tmp_arr[] = '(' . implode(',' , $insert_values[$row_id]). ')';
			if(count($tmp_arr) == 2)
			{
				break;
			}
		}
	break;
	
	}

}

$sql = "insert into `ecm_building` ( ". implode(',' , $f_arr) .") VALUES " . implode(',' , $tmp_arr);
echo ($sql);

// Echo memory peak usage
echo date('H:i:s') . " Peak memory usage: " . (memory_get_peak_usage(true) / 1024 / 1024) . " MB\r\n";

// Echo done
echo date('H:i:s') . " Done writing files.\r\n";

function get_region_id($region_name)
{
	return 1;
}
