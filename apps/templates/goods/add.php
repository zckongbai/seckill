<!DOCTYPE html>
<html>
<head>
	<title>添加商品</title>
</head>
<body>
<div>
	<form action="" method="post">
		商品编号:	<input type="txt" name="sn" value="1002"><br/>
		商品数量:	<input type="txt" name="number" value="100"><br/>
		每人最多购买数量:	<input type="txt" name="allow_num" value="10"><br/>
		秒杀开始时间(5分钟后):	<input type="txt" name="start_time" value="<?php echo date("Y-m-d H:i:s", time()+300);?>"><br/>
		<input type="submit" value="添加">
	</form>
</div>
</body>
</html>