/**
 * ��ȡ��������
 * @param	int	status					����״̬
 * @param	int	reception_time			����ʱ��
 * @param	int	expected_delivery_time	Ԥ���ʹ�ʱ��
 * @return	int ��������
 */
function lgsProgress(status, reception_time, expected_delivery_time) {
	var progress = 0; // ��������
	var nowUnix = getNowUnix(); // ��ǰʱ���
	
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