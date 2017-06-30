<?php
namespace App\Controller;
use Swoole;

class Goods extends Swoole\Controller
{
    function __construct($swoole)
    {
        parent::__construct($swoole);
    }

    function index()
    {
        // echo __METHOD__;
        $model = Model('Goods');
        $get =  $this->request->get;
        $arr = array(
                'page'  =>  $get['page'] ? : 1,
            );
        $goods = $model->gets($arr);
        $this->assign('goods', $goods);
        $this->display();
    }

    function show()
    {
    	$get = $this->request->get;
    	$id = $get['id'];
    	$model = Model('Goods');
    	$good = $model->get($id);
    	$this->assign('good', $good);
        $this->display();
    }

    function add()
    {
        $data = $this->request->post;

        if ($data) 
        {
            $model = Model("Goods");
            $res = $model->put($data);
            $this->log->put( json_encode(['post'=>$data]) );
            $this->log->put( json_encode(['res'=>$res]) );
            $this->log->flush();
            if ($res) 
            {
                $this->http->redirect('index');
            }
        }
        $this->display();
    }

    function buy()
    {
    	// 先验证数据
        $data = $this->request->get;
        $this->log->put( json_encode(['get'=>$data]) );
        $this->log->flush();
        if ( !isset($data['id']) || !isset($data['good_num']) ) 
        {
        	return $this->message("10", '数据非法');
        }
        $goods_model = Model('Goods');
        $good = $goods_model->get($data['id']);
        if ( $data['good_num'] > $good['allow_num'] ) 
        {
        	return $this->message("11", '超过购买限制');
        }
        if ( $data['good_num'] > $good['number'] ) 
        {
        	return $this->message("12", '商品余额不足');
        }

		$goods_model->db->start();
		$update = array(
				'number'	=>	$good['number'] - $data['good_num'],
			);
		$update_res = $goods_model->set($good['id'], $update);

		$goods_log_model = Model('GoodsLog');
		$log_data = array(
				'good_id'	=>	$good['id'],
				'good_num'	=>	$data['good_num'],
				'create_time'	=>	date("Y-m-d H:i:m"),
			);
		$log_res = $goods_log_model->put($log_data);

		if ($update_res && $log_res) 
		{
			$goods_model->db->commit();
			return $this->json(array('pay_url'=>"/pay/index"), "00", "成功");
		}

		$goods_model->db->rollback();
		return $this->message("13", "失败");
    }



}