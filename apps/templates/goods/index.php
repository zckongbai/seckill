<!DOCTYPE html>
<html>
<head>
    <title>商品列表</title>
</head>
<body>
<div>
	<h4>商品列表</h4>
	<table>
	    <tr>
	        <th>编号</th>
	        <th>商品数量</th>
	        <th>每人最多购买数量</th>
	        <th>秒杀开始时间</th>
	        <th>查看</th>
	    </tr>
	    <?php foreach ($this->tpl_var['goods'] as $value): ?>
	    <tr>
	        <td><?php echo $value['sn'];?></td>
	        <td><?php echo $value['number'];?></td>
	        <td><?php echo $value['allow_num'];?></td>
	        <td><?php echo $value['start_time'];?></td>
	        <td><a href="/goods/show?id=<?php echo $value['id'];?>">查看</a></td>
	    </tr>
	    <?php endforeach; ?>
	</table>
</div>
</body>
</html>