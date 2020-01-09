<?php


namespace app\index\controller;


use app\common\controller\Frontend;
use app\common\model\ExaminationPaper;
use app\common\model\Examination as ExaminationModel;
use app\common\model\Questions;
use app\common\service\Helper;
use think\Db;

class Examination extends Frontend
{
    protected $noNeedRight = '*';
    protected $layout = '';

    /**
     * 考试
     * @return string
     * @throws \think\Exception
     */
    public function index()
    {
        $sql = 'SELECT ep.id,ep.name,e.id eid FROM qkl_examination_paper ep LEFT JOIN qkl_examination e ON ep.id = e.examination_paper_id AND e.end_time > 0  WHERE ep.user_id = '.$this->auth->id ;
        $examinationPapers = Db::query($sql);
        $adList = model('Advertising')->where('status', 'normal')->limit(5)->column('id,title,image,link_url', 'id');
        $this->assign('ads', $adList);
        $this->assign('examinationPapers', $examinationPapers);
        return $this->view->fetch();
    }

    /**
     * 考试结果
     * @param $eid
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($eid)
    {
        $examination = ExaminationModel::findOrFail($eid);
        $totalQuestions = count($examination->answer_results);
        if ($examination->end_time <= 0) {//针对未提交退出等异常处理
            Db::startTrans();
            try{
                $examination->end_time = (time() > $examination->begin_time * 30 * $totalQuestions) ? $examination->begin_time * 30 * $totalQuestions : time();
                $examination->score = Helper::calculateScore($examination->answer_results);
                $examination->save();
                Helper::ExaminationRewardsAndPenalties($examination);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                dump($e->getMessage());
                abort('401','考试异常，请联系管理员');
            }
        }
        $rightResultNum = 0;
        foreach ($examination->answer_results as $result) {
            if ($result['answer'] == $result['result']) {
                $rightResultNum++;
            }
        }
        $accuracy = bcdiv($rightResultNum, $totalQuestions, 2) * 100;
        $useMinutes = ceil(($examination->end_time - $examination->begin_time) / 60);
        $this->assign('examination', $examination);
        $this->assign('rightResultNum', $rightResultNum);
        $this->assign('totalQuestions', $totalQuestions);
        $this->assign('accuracy', $accuracy);
        $this->assign('useMinutes', $useMinutes);
        $this->assign('answerResult', $examination->answer_results);
        return $this->view->fetch();
    }

    /**
     * 开始答题
     * @param $epid
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function answer($epid)
    {
        $examinationPaper = ExaminationPaper::findOrFail($epid);
        $questions = model('Questions')->whereIn('id', $examinationPaper->questions)->column('*', 'id');
        $examination = model('Examination')->where('user_id', $this->auth->id)->where('examination_paper_id', $epid)->find();
        if (!$examination) {
            $examinationData = [
                'user_id' => $this->auth->id,
                'examination_paper_id' => $epid,
                'begin_time' => time(),
            ];
            model('Examination')->insert($examinationData);
            $eid = model('Examination')->getLastInsID();
        } else {
            if ($examination->end_time > 0) {
                abort(401, '已经交卷无法考试！！！');
            }
            $eid = $examination->id;
        }
        $answers = [];
        foreach ($questions as $id => $question) {
            $answers[$id] = '';
        }
        $this->assign('questions', $questions);
        $this->assign('examinationPaper', $examinationPaper);
        $this->assign('eid', $eid);
        $this->assign('answers', json_encode($answers));
        return $this->view->fetch();
    }

    /**
     * 答题卡
     * @return string
     * @throws \think\Exception
     */
    public function sheet()
    {
        return $this->view->fetch();
    }


    /**
     * 交卷
     */
    public function submit()
    {
        $eid = $this->request->post('eid');
        $answer = $this->request->post('answer/a');
        $action = $this->request->post('action','0');
        $action && Db::startTrans();
        try {
            $model = model('Examination');
            $examination = $model->findOrFail($eid);
            $examination->answer_results = $answer;
            if($action == '1'){
                $examination->end_time = time() > $examination->begin_time * 30 * count($answer) ?$examination->begin_time * 30 * count($answer):time() ;
                $examination->score =  Helper::calculateScore($answer);
                $examination->answer_results = $answer;
                Helper::ExaminationRewardsAndPenalties($examination);
            }
            $examination->save();
            $action && Db::commit();
        } catch (\Exception $e) {
            $action && Db::rollback();
            return $this->error('', null, ['token' => $this->request->token()]);
        }

        return $this->success('', null, ['token' => $this->request->token()]);
    }
}