<?php


namespace app\index\controller;


use app\common\controller\Frontend;

class Rule extends Frontend
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    /**
     * 规章列表
     * @return string
     * @throws \think\Exception
     */
    public function index()
    {
        $rules = model('Rule')->where('status', 'normal')->column('title', 'id');
        $this->assign('rules', $rules);
        return $this->view->fetch();
    }

    /**
     * 详情
     * @param $id
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($id)
    {
        $rule = model('Rule')->findOrFail($id);
        $this->assign('rule', $rule);
        return $this->view->fetch();
    }
}