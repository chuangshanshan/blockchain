<?php


namespace app\index\controller;


use app\common\controller\Frontend;
use app\common\model\PunishAppeal;
use app\common\service\Helper;
use think\Db;
use think\Validate;

class Punish extends Frontend
{
    protected $noNeedRight = '*';
    protected $layout = '';

    /**
     * 惩罚列表
     * @return string|void
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $punishs = Model('Punish')->where('user_id', $this->auth->id)->field('id,status,title')->order(
            'createtime',
            'desc'
        )->paginate(20)->toArray();
        if ($this->request->isAjax()) {
            return $this->success('', null, $punishs['data']);
        }
        $this->assign('punishs', $punishs);
        return $this->view->fetch();
    }

    /**
     * 惩罚详情
     * @param $id
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($id)
    {
        $punish = Model('Punish')->where('user_id', $this->auth->id)->findOrFail($id);
        $this->assign('punish', $punish);
        return $this->view->fetch();
    }

    /**
     * 接受惩罚
     * @param $id
     */
    public function accept($id)
    {
        Db::startTrans();
        try {
            $punish = model('Punish')->where('user_id', $this->auth->id)->whereIn('status', [1, 2])->findOrFail($id);
            $punish->save(
                [
                    'status' => 3,
                ],
                [
                    'id' => $id
                ]
            );
            //直接会扣用户余额
            Helper::changeUserBalance($this->auth->id, $punish->amount, 4, '', $punish->id);
            Db::commit();
        } catch (\Exception $exception) {
            Db::rollback();
            abort(401, $exception->getMessage());
        }
        return $this->redirect('/index/punish/detail?id=' . $id);
    }

    /**
     * 申诉
     * @return string|void
     * @throws \think\Exception
     */
    public function appeal()
    {
        if ($this->request->isAjax()) {
            $content = $this->request->post("content");
            $image = $this->request->post("image");
            $punish_id = $this->request->post("punish_id");
            $token = $this->request->post('__token__');
            $rule = [
                'content' => 'require',
                'image' => 'require',
                '__token__' => 'token',
            ];

            $msg = [
            ];
            $data = [
                'content' => $content,
                'image' => $image,
                '__token__' => $token,
            ];
            $field = [
                'content' => __('申诉内容'),
                'image' => __('申诉图片'),
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                return $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }

            Db::startTrans();
            try {
                //更新惩罚记录为受理中
                $punish = \app\common\model\Punish::findOrFail($punish_id);
                $punish->save(['status' => 2], ['id' => $punish_id]);
                //新增惩罚申诉记录
                $punishAppealData = [
                    'user_id' => $this->auth->id,
                    'punish_id' => $punish_id,
                    'content' => $content,
                    'image' => $image,
                ];
                $punishAppeal = new PunishAppeal($punishAppealData);
                $punishAppeal->save();
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