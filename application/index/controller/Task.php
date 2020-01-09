<?php


namespace app\index\controller;


use app\common\controller\Frontend;

class Task extends Frontend
{
    protected $noNeedRight = '*';
    protected $layout = '';

    /**
     * 任务
     * @return string
     * @throws \think\Exception
     */
    public function index()
    {
        return $this->view->fetch();
    }
}