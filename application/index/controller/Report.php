<?php


namespace app\index\controller;


use app\common\controller\Frontend;
use app\common\model\PunishAppeal;
use think\Db;
use think\Validate;

class Report extends Frontend
{

    protected $noNeedRight = '*';
    protected $layout = '';

    /**
     * 举报
     * @return string|void
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $reports = model('Report')->where('user_id', $this->auth->id)->order('createtime', 'desc')->field(
            'id,accused_user,status'
        )->paginate(20)->toArray();
        if ($this->request->isAjax()) {
            return $this->success('', null, $reports['data']);
        }
        $this->assign('reports', $reports);
        return $this->view->fetch();
    }

    /**
     * 举报详情
     * @param $id
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($id)
    {
        $report = model('Report')->where('user_id', $this->auth->id)->findOrFail($id);
        $this->assign('report', $report);
        return $this->view->fetch();
    }

    /**
     * 新增举报
     * @return string|void
     * @throws \think\Exception
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $association_name = $this->request->post("association_name");
            $association_admin = $this->request->post("association_admin");
            $association_aide = $this->request->post("association_aide");
            $accused_user = $this->request->post("accused_user");
            $screenshot_image = $this->request->post("screenshot_image/a");
            $content = $this->request->post("content");
            $token = $this->request->post('__token__');
            $rule = [
                'association_name' => 'require',
                'association_admin' => 'require',
                'association_aide' => 'require',
                'accused_user' => 'require',
                'screenshot_image' => 'require',
                'content' => 'require',
                '__token__' => 'token',
            ];

            $msg = [
            ];
            $data = [
                'association_name' => $association_name,
                'association_admin' => $association_admin,
                'association_aide' => $association_aide,
                'accused_user' => $accused_user,
                'screenshot_image' => $screenshot_image,
                'content' => $content,
                '__token__' => $token,
            ];
            $field = [
                'association_name' => __('社群名称'),
                'association_admin' => __('社群群主'),
                'association_aide' => __('小助手'),
                'accused_user' => __('举报相关人'),
                'content' => __('详细内容'),
                'screenshot_image' => __('截图'),
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                return $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            Db::startTrans();
            try {
                //新增举报记录
                $punishAppealData = [
                    'user_id' => $this->auth->id,
                    'association_name' => $association_name,
                    'association_admin' => $association_admin,
                    'association_aide' => $association_aide,
                    'accused_user' => $accused_user,
                    'content' => $content,
                ];
                $report = new \app\common\model\Report($punishAppealData);
                $report->screenshot_image=$screenshot_image;
                $report->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return $this->error($e->getMessage(), null, ['token' => $this->request->token()]);
            }
            return $this->success();
        }
        return $this->view->fetch();
    }

}