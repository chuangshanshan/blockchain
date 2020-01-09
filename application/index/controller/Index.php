<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\model\ExaminationPaper;
use app\common\model\News;
use app\common\model\SystemNotices;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    /**
     * 首页
     * @return string
     * @throws \think\Exception
     */
    public function index()
    {
        $newList = model('News')->where('status', 'normal')->order('createtime', 'desc')->field(
            'id,title,show_image'
        )->paginate(5)->toArray();
        if ($this->request->isAjax()) {
            return $this->success('', null, $newList['data']);
        }
        $adList = model('Advertising')->where('status', 'normal')->limit(5)->column('id,title,image,link_url', 'id');
        $systemNoticeList = model('SystemNotices')->order('createtime', 'desc')->limit(5)->column('title', 'id');
        $needExamination = 0;
        //是否有考试
        if($this->auth->isLogin()){
            $examinationPapers = ExaminationPaper::where('user_id',$this->auth->id)->count();
            if($examinationPapers > 0){
                $examinations = \app\common\model\Examination::where('user_id',$this->auth->id)->count();
                if($examinationPapers > $examinations){
                    $needExamination = 1;
                }
            }
        }
        $this->assign('newses', $newList);
        $this->assign('ads', $adList);
        $this->assign('systemNotices', $systemNoticeList);
        $this->assign('needExamination', $needExamination);
        return $this->view->fetch();
    }

    /**
     * 新闻详情
     * @param $id
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($id)
    {
        $news = model('News')->where('status', 'normal')->findOrFail($id);
        $this->assign('news', $news);
        return $this->view->fetch();
    }

}
