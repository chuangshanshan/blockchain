<?php


namespace app\index\controller;


use app\common\controller\Frontend;

class Notice extends Frontend
{
    protected $noNeedRight = '*';
    protected $layout = '';

    /**
     * 首页
     * @return string
     * @throws \think\Exception
     */
    public function index()
    {
        $systemNotices = model('SystemNotices')->order('createtime', 'desc')->limit(50)->column(
            'id,title,0 type',
            'createtime'
        );
        $notices = model('Notices')->where('user_id', $this->auth->id)->order('createtime', 'desc')->limit(50)->column(
            'id,title,type',
            'createtime'
        );
        $notices = $systemNotices + $notices;
        $this->assign('notices', $notices);
        return $this->view->fetch();
    }

    /**
     * 通知详情
     * @param $id
     * @param $type
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($id, $type)
    {
        if ($type) {
            $notice = model('Notices')->where('user_id', $this->auth->id)->findOrFail($id);
        } else {
            $notice = model('SystemNotices')->findOrFail($id);
        }
        $this->assign('notice', $notice);
        return $this->view->fetch();
    }
}