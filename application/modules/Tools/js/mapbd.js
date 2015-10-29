//地图对象
var map;
var level;
var store_subzone_coord;
var myValue;
var localSearchSet;
var all_subzone_coord;

function initPage()
{
	$( "#div_query_type" ).tabs();
	$( "#button_store_query_free,#button_store_query_name,#button_zone_query" ).button();
	$( "#select_address_city").selectmenu(
	{
	});
	$('#store_name').autocomplete(
	{
		source:"/tools/mapbd/searchstorebyname",
		minLength: 2,
		select:getStoreByName
	});
	$( "#select_city").selectmenu(
	{
		change:getSubRegionById
	});
	$( "#select_zone").selectmenu(
	{
		change:setSubRegionCoordBySelect
	});
	initAddressInput();
}

function G(id)
{
    return document.getElementById(id);
}

function initAddressInput()
{
	initMap('','');
	var ac = new BMap.Autocomplete(    //建立一个自动完成的对象
    {
    	"input" : "query_address",
    	"location" : map
	});
	ac.addEventListener("onhighlight", function(e)
	{//鼠标放在下拉列表上的事件
		var str = "";
		var _value = e.fromitem.value;
		var value = "";
		if (e.fromitem.index > -1)
			 value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
		str = "FromItem<br />index = " + e.fromitem.index + "<br />value = " + value;
		value = "";
		if (e.toitem.index > -1)
		{
			value = e.toitem.value;
			value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
		}
		str += "<br />ToItem<br />index = " + e.toitem.index + "<br />value = " + value;
		//G("searchResultPanel").innerHTML = str;
	});
	ac.addEventListener("onconfirm", function(e)
	{    //鼠标点击下拉列表后的事件
		var _value = e.item.value;
		myValue = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
		//G("searchResultPanel").innerHTML ="onconfirm<br />index = " + e.item.index + "<br />myValue = " + myValue;
		setPlace();
	});
}

function initAddressInputMap()
{
	var region_id = $('#select_address_city').val();
	var json_zone = $.parseJSON($('#zone_array').val());
	var zone_info = json_zone[region_id]['subzone'];
	var zone_coord = '';
	for (var i in zone_info)
	{
		if (zone_info[i].region_id==0)
			continue;
		else
		{
			if (zone_coord=='')
				zone_coord = zone_info[i].region_coord;
			else
				zone_coord += '&'+zone_info[i].region_coord;
		}
	}
	drawAllPolygon(zone_coord);
}

function setPlace()
{
    map.clearOverlays();    //清除地图上所有覆盖物
    localSearchSet = new BMap.LocalSearch(map, { //智能搜索
		onSearchComplete: setPlaceMarker
    });
    localSearchSet.search(myValue);
}

function setPlaceMarker()
{
	initAddressInputMap();
	var pp = localSearchSet.getResults().getPoi(0).point;    //获取第一个智能搜索的结果
	map.centerAndZoom(pp, 14);
	var marker = new BMap.Marker(pp);
	map.addOverlay(marker);    //添加标注
	var p = marker.getPosition();
	$('#label_query_address_coord').html(p.lng+','+p.lat);
	checkAddressArea(p.lng,p.lat);
}

function checkAddressArea(lng,lat)
{
	var region_id = $('#select_address_city').val();
	var json_zone = $.parseJSON($('#zone_array').val());
	var zone_info = json_zone[region_id]['subzone'];
	var zone_coord = '';
	var pt = new BMap.Point(lng,lat);
	for (var i in zone_info)
	{
		if (zone_info[i].region_id==0)
			continue;
		else
		{
			var pts = disposePoints(zone_info[i].region_coord);
			var ply = new BMap.Polygon(pts);
			result = ptInPolygon(pt,ply);
			if (result==true)
			{
				$('#label_query_address_detail').html('位于 '+zone_info[i].region_name+' 区域 之内');
				break;
			}
			else
			{
				//得到中心坐标
				var split_coord_array = zone_info[i].region_coord.split(';');
				var coord_array = [];
				for (var j in split_coord_array)
				{
					var split_coord = split_coord_array[j].split(',');
					coord_array[j] = [split_coord[0],split_coord[1]];
				}
				var center_coord = d3.geom.polygon(coord_array).centroid();
				console.log(center_coord);
				$('#label_query_address_detail').html('位于 所有区域 之外');
			}
		}
	}
}

function ptInPolygon(pt,ply)
{
	return BMapLib.GeoUtils.isPointInPolygon(pt,ply);
}

function queryStoreByFree()
{
	var store_coord = $('#store_coord').val();
	var store_bc_coord = $('#store_bc_coord').val();
	if (store_coord=='')
		return false;
	if (store_bc_coord=='')
		return false;
	arr_store_coord = store_coord.split(',');
	initMap([arr_store_coord[0],arr_store_coord[1]],14);
	drawMarkPoint([arr_store_coord[0],arr_store_coord[1]]);
	drawPolygon(store_bc_coord);
}

function queryZone()
{
	var city_name = $("#select_city option:selected").text()
	var zone_coord = $('#zone_coord').val();
	initMap(city_name,'');
	drawAllPolygon(zone_coord);
}

function initMap(point,level)
{
	if (point=='')
		point = '北京';
	if (level=='')
		level = 12;
	map = new BMap.Map("mss_map");
	if ($.isArray(point))
		map.centerAndZoom(new BMap.Point(point[0],point[1]),level);
	else
		map.centerAndZoom(point,level);
	map.addControl(new BMap.NavigationControl());
	map.addControl(new BMap.MapTypeControl());
	map.enableScrollWheelZoom();
	map.enableContinuousZoom();
}

function drawMarkPoint(point)
{
	if (point=='')
		return false;
	var marker = new BMap.Marker(new BMap.Point(point[0],point[1]));
	map.addOverlay(marker);
}

function drawAllPolygon(points)
{
	var points_array = points.split('&');
	for (var i in points_array)
	{
		drawPolygon(points_array[i]);
	}
}

function drawPolygon(points)
{
	pointsObjects = disposePoints(points);
	//var polygon = new BMap.Polyline(pointsObjects, {strokeColor:"blue", strokeWeight:4, strokeOpacity:1});
	var polygon = new BMap.Polygon(pointsObjects, {strokeColor:"blue", strokeWeight:6, strokeOpacity:0.4});
	map.addOverlay(polygon);
}

function disposePoints(points)
{
	var pointsObjects = new Array();
	var pointsArray = points.split(";");
	for (var i=0;i<pointsArray.length;i++)
	{
		coordinate = pointsArray[i].split(',');
		pointsObjects.push(new BMap.Point(coordinate[0],coordinate[1]));
	}
	return pointsObjects;
}

function getStoreByName(event,ui)
{
	$('#store_coord').val(ui.item.point);
	$('#store_bc_coord').val(ui.item.zone);
}

function getSubRegionById(event,data)
{
	var region_id = data.item.value;
	if (region_id==''||region_id==0)
		return false;
	var json_zone = $.parseJSON($('#zone_array').val());
	var zone_info = json_zone[region_id];
	addZoneToSelect(zone_info['subzone']);
}

function addZoneToSelect(zone_info)
{
	//$('#store_subzone_coord').val(zone_info);
	store_subzone_coord = zone_info
	$('#select_zone').empty();
	$('#zone_coord').val('');
	for (var i in zone_info)
	{
		if (zone_info[i].region_id==0)
			$('#select_zone').append('<option value="'+zone_info[i].region_id+'" selected="selected">'+zone_info[i].region_name+'</option>');
		else
		{
			$('#select_zone').append('<option value="'+zone_info[i].region_id+'">'+zone_info[i].region_name+'</option>');
			if ($('#zone_coord').val()=='')
				$('#zone_coord').val(zone_info[i].region_coord);
			else
				$('#zone_coord').val($('#zone_coord').val()+'&'+zone_info[i].region_coord);
		}
	}
	$('#select_zone').selectmenu('refresh',true);
}

function setSubRegionCoordBySelect(event,data)
{
	var region_id = data.item.value;
	var zone_info = store_subzone_coord;
	setSubRegionCoord(region_id,zone_info)
}

function setSubRegionCoord(region_id,zone_info)
{
	$('#zone_coord').val('');
	if (region_id!=''&&region_id>0)
	{
		for (var i in zone_info)
		{
			if (zone_info[i].region_id==region_id)
				$('#zone_coord').val(zone_info[i].region_coord);
		}
	}
	else
	{
		addZoneToSelect(zone_info);
	}
}