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
        // $this->log->flush();
        if ( !isset($data['id']) || !isset($data['good_num']) ) 
        {
        	return $this->message("10", '数据非法');
        }
        $goods_model = Model('Goods');

        $res = $goods_model->buy($data['id'], $data['good_num']);

        $this->log->put( json_encode(['res'=>$res]) );
        $this->log->flush();

        if ($res['code'] == '00')
        {
        	return $this->json(array('pay_url'=>"/pay/index"), $res['code'], $res['message']);
        }

		return $this->message($res['code'], $res['message']);

    }



}