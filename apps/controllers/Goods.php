<?php
namespace App\Controller;
use Swoole;

class Goods extends Swoole\Controller
{
    function __construct($swoole)
    {
        parent::__construct($swoole);
    }

    // function __beforeAction($mvc)
    // {

    // }

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

    /**
     * 添加商品接口 v2
     * 增加redis缓存
     */
    function add()
    {
        $data = $this->request->post;

        if ($data) 
        {
            $model = Model("Goods");
            $res = $model->put($data);
            $this->log->put( json_encode(['post'=>$data]) );
            $this->log->put( json_encode(['res'=>$res]) );
            if ($res) 
            {
            	// 入redis
            	$data['id'] = $res;
            	$redis_res = $this->redis->hMset("goods_{$res}", $data);
            	$this->log->put( json_encode(['redis_res'=>$redis_res]) );
                $this->http->redirect('index');
            }
            $this->log->flush();
        }
        $this->display();
    }

    /**
     * 购买接口 v2
     * 增加缓存, 异步同步数据
     */
    function buy()
    {
    	// 先验证数据
        $data = $this->request->get;
        $this->log->put( json_encode(['get'=>$data]) );
        if ( !isset($data['id']) || !isset($data['good_num']) ) 
        {
        	$this->log->flush();
        	return $this->message("10", '数据非法');
        }

        // 限流
    	if (!$this->__before_buy())
    	{
        	return $this->message("13", '商品数量不足');
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

    /**
     * 限流
     * 用redis缓存做判断
     */
    protected function __before_buy()
    {
    	$get = $this->request->get;
		$good = $this->__get_goods_by_id($get['id']);
		if (!$good || $good['number'] < $get['good_num']){
			return false;
		}
		return true;
    }

    /**
     * 根据id查goods
     */
    protected function __get_goods_by_id($id)
    {
    	$data = $this->redis->hGet("goods_{$id}");
    	if (!$data)
    	{
    		$model = Model('Goods');
    		$data = $model->get($id);
    		if (!$data)
    		{
    			$this->redis->hSet("goods_{$id}", $data);
    		}
    	}
    	return $data;
    }




}