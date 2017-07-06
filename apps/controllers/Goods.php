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
            	$redis_res = $this->redis->hMset("goods:{$res}", $data);
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
        $get = $this->request->get;
        $this->log->put( json_encode(['get'=>$get]) );
        if ( !isset($get['id']) || !isset($get['good_num']) ) 
        {
        	$this->log->flush();
        	return $this->message("10", '数据非法');
        }

        // 限流
    	if (!$this->__before_buy())
    	{
        	return $this->message("13", '商品数量不足');
    	}

    	// 操作数据库改为异步
        // 已经判断过数量了 ^
        $good = $this->redis->hGetAll("goods:{$get['id']}");

        // if ( !($good['allow_num'] >= $good['good_num']) && ($good['number'] > $good['sell_number']) && (($good['number'] - $good['sell_number']) >= $get['good_num']) )
        // {
        //     return $this->message("13", '商品数量不足');
        // }

        if (  $good['allow_num'] < $good['good_num'] )
        {
            return $this->message("13", '商品数量不足');
        }

        if (  $good['number'] <= $good['sell_number'] )
        {
            return $this->message("13", '商品数量不足');
        }
        
        if (  $good['number'] - $good['sell_number'] < $get['good_num'] )
        {
            return $this->message("13", '商品数量不足');
        }



        $this->redis->watch("goods:{$get['id']}");
        $this->redis->multi();
        $this->redis->hIncrBy("goods:{$get['id']}", "sell_number", $get['good_num']);
        $redis_res = $this->redis->exec();

        $this->log->put( json_encode(['buy_good_res'=>$redis_res,'data'=>$get]) );

        if (!$redis_res)
        {
            return $this->message("12", '失败');
        }

        $task_data = array(
        		'type'	=>	'goods_buy',
        		'id'	=>	$get['id'],
        		'good_num'	=>	$get['good_num'],
        		'time'	=>	time(),
        	);
        $res = $this->server->getSwooleServer()->task($task_data);
        $this->log->put( json_encode(['task-res'=>$res]) );
        $this->log->flush();
    	return $this->json(array('pay_url'=>"/pay/index"), "00", "成功");
    }

    /**
     * 限流
     * 用redis缓存做判断
     */
    protected function __before_buy()
    {
    	$get = $this->request->get;
		$good = $this->__get_goods_by_id($get['id']);
        $this->log->put( json_encode(['__before_buy-good'=>$good]) );
        $this->log->flush();
		// if ( !$good || ($good['allow_num'] < $good['good_num']) || (($good['number'] - $good['sell_number']) < $get['good_num']) ){
    if ( $good && ($good['allow_num'] >= $good['good_num']) && (($good['number'] - $good['sell_number']) >= $get['good_num']) ){
            return true;
		}
		return false;
    }

    /**
     * 根据id查goods
     */
    protected function __get_goods_by_id($id)
    {
    	$data = $this->redis->hGetAll("goods:{$id}");
        $this->log->put( json_encode(['__get_goods_by_id-good'=>$data, 'id'=>"goods:{$id}"]) );
    	if (!$data)
    	{
    		$model = Model('Goods');
    		$data = $model->get($id)->get();
            $this->log->put( json_encode(['__get_goods_by_id-model-good'=>$data]) );
    		if ($data)
    		{
    			$res = $this->redis->hMset("goods:{$id}", $data);
                $this->log->put( json_encode(['__get_goods_by_id-redis-good'=>$res]) );
    		}
    	}
        $this->log->flush();
    	return $data;
    }




}