/**
 * 获取一级SELECT元素
 * @return 
 */
function getBigSelect() {
	$.ajax({
		type: "POST",
		url: "/custom/crmorder/ajaxGetCause",
		data: "type=initbig",
		dataType: "html",
		cache: false,
		success: function(data, textStatus){
			$("#bigSelect").empty();
			$("#bigSelect").append(data);
		},
		error: function(){
		}
	});
}
/**
 * 获取二级SELECT元素
 * @return 
 */
function getSmallSelect(){
	var bigVal = $("#bigSelect option:selected").index();
	val = bigVal-1;
	if(bigVal != ''){
		$.ajax({
			type: "POST",
			url: "/custom/crmorder/ajaxGetCause",
			data: "type=getsmall&val="+val,
			dataType: "html",
			cache: false,
			success: function(data, textStatus){
				$("#smallSelect").empty();
				$("#smallSelect").append(data);
			},
			error: function(){
			}
		});
	} else {
		$("#smallSelect").html('<option value="0">请选择原因</option>');
	}
}
/**
 * socket-订单状态更新后广播
 * @param	str	uid			用户ID
 * @param	str	username	用户名
 * @param	int	order_id	订单ID
 */
function socketUpdateOrder(uid, username, order_id) {
	socket.emit('update order', { uid: uid, username: username, order_id: order_id });
	socket.emit('unlock edit order', { uid: uid, username: username, order_id: order_id });
}