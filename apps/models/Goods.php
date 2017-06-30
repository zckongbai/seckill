<?php
namespace App\Model;
use Swoole;

class Goods extends Swoole\Model
{
    /**
     * 表名
     * @var string
     */
    public $table = 'goods';
    public $primary_key = 'id';

    function buy()
    {
        
    }

}