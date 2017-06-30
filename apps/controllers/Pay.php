<?php
namespace App\Controller;
use Swoole;

class Pay extends Swoole\Controller
{
    function __construct($swoole)
    {
        parent::__construct($swoole);
    }

    function index()
    {
        echo __METHOD__;
    }
}