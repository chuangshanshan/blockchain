<?php


namespace app\index\controller;


use app\common\controller\Frontend;
use think\Db;
use think\Validate;

class Association extends Frontend
{
    protected $noNeedRight = '*';
    protected $layout = '';

    /**
     * 我的社群
     * @return string|void
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $associations = model('Association')->where('user_id', $this->auth->id)->order('createtime', 'desc')->field('id,name,status')->paginate(20)->toArray();
        if ($this->request->isAjax()) {
            return $this->success('', null, $associations['data']);
        }
        $this->assign('associations', $associations);
        return $this->view->fetch();
    }

    /**
     * 社群详情
     * @param $id
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($id)
    {
        $association = model('Association')->where('user_id', $this->auth->id)->findOrFail($id);
        $this->assign('association', $association);
        return $this->view->fetch();
    }

    /**
     * 社群报备
     * @return string|void
     * @throws \think\Exception
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $name = $this->request->post("name");
            $owner = $this->request->post("owner");
            $teacher = $this->request->post("teacher");
            $qr_code = $this->request->post("qr_code");
            $token = $this->request->post('__token__');
            $rule = [
                'name' => 'require',
                'owner' => 'require',
                'teacher' => 'require',
                'qr_code' => 'require',
                '__token__' => 'token',
            ];

            $msg = [
            ];
            $data = [
                'name' => $name,
                'owner' => $owner,
                'teacher' => $teacher,
                'qr_code' => $qr_code,
                '__token__' => $token,
            ];
            $field = [
                'name' => __('社群名称'),
                'owner' => __('社群群主'),
                'teacher' => __('老师名称'),
                'qr_code' => __('社群二维码'),
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                return $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            Db::startTrans();
            try {
                //新增社群记录
                $associationData = [
                    'user_id' => $this->auth->id,
                    'name' => $name,
                    'owner' => $owner,
                    'teacher' => $teacher,
                    'qr_code' => $qr_code,
                ];
                $association = new \app\common\model\Association($associationData);
                $association->save();
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