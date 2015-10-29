/**
 * 获取物流进度
 * @param	int	status					物流状态
 * @param	int	reception_time			接收时间
 * @param	int	expected_delivery_time	预计送达时间
 * @return	int 物流进度
 */
function lgsProgress(status, reception_time, expected_delivery_time) {
	var progress = 0; // 物流进度
	var nowUnix = getNowUnix(); // 当前时间戳
	
	switch (status) {
		case 0:
			progress = 20;
			break;
		case 1:
			if(reception_time < nowUnix && nowUnix < expected_delivery_time) {
				var rate = accDiv((nowUnix - reception_time), (expected_delivery_time - reception_time));
				if(rate >= 0.8) {
					progress = 80;
				} else {
					progress = 50;
				}
			} else if(nowUnix >= expected_delivery_time) {
				progress = 80;
			} else {
				progress = 50;
			}
			break;
		case 2:
			progress = 100;
			break;
	}
	return progress;
}