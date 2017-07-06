<?php
namespace Swoole\Protocol;
use Swoole;

class AppServerException extends \Exception
{

}

class AppServer extends HttpServer
{
    protected $router_function;
    protected $apps_path;

    function onStart($serv)
    {
        parent::onStart($serv);
        if (empty($this->apps_path))
        {
            if (!empty($this->config['apps']['apps_path']))
            {
                $this->apps_path = $this->config['apps']['apps_path'];
            }
            else
            {
                throw new AppServerException("AppServer require apps_path");
            }
        }
        $php = Swoole::getInstance();
        $php->addHook(Swoole::HOOK_CLEAN, function(){
            $php = Swoole::getInstance();
            //模板初始化
            if (!empty($php->tpl))
            {
                $php->tpl->clear_all_assign();
            }
        });
    }

    /**
     * 处理请求
     * @param Swoole\Request $request
     * @return Swoole\Response
     */
    function onRequest(Swoole\Request $request)
    {
        return Swoole::getInstance()->handlerServer($request);
    }


    /**
     * 异步处理
     * 
     */
    function onTask($serv,$task_id,$from_id, $data) 
    {
        $log = Swoole\Factory::getLog();
        $log->put(json_encode(['task_id'=>$task_id,'from_id'=>$from_id,'data'=>$data]));

        if (!$data) 
        {
            return ;
        }
        $return = true;

        switch ($data['type']) 
        {
            // 商品购买
            case 'goods_buy':
                $goods_model = Model('Goods');
                $res = $goods_model->buy($data['id'], $data['good_num']);
                $log->put( json_encode(['onTask_res'=>$res]) );

                break;

            default:
                # code...
                break;
        }
        $log->flush();
        return $return;
    }

    function onFinish($serv,$task_id, $data) {
    //     echo "Task {$task_id} finish\n";
    //     echo "Result: {$data['msg']}\n";
    }


}
