<?php


namespace app\index\controller;


use app\common\controller\Frontend;
use app\common\model\UserRedPacket;
use think\Db;
use think\Validate;

class RedPacket extends Frontend
{
    protected $noNeedRight = '*';
    protected $layout = '';

    /**
     * 红包列表
     * @return string|void
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $redPackets = model('UserRedPacket')->where('user_id', $this->auth->id)->field(
            'id,amount,is_shared,createtime'
        )->order('createtime', 'desc')->paginate(10)->toArray();
        foreach ($redPackets['data'] as & $redPacket) {
            $redPacket['amount'] = bcdiv($redPacket['amount'], 100, 2);
            $redPacket['createtime'] = date('y/m/d H:i', $redPacket['createtime']);
        }
        if ($this->request->isAjax()) {
            return $this->success('', null, $redPackets['data']);
        }
        $this->assign('redPackets', $redPackets);
        return $this->view->fetch();
    }

    /**
     * 添加红包任务
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $association_name = $this->request->post("association_name");
            $amount = $this->request->post("amount") * 100;
            $is_shared = $this->request->post("is_shared");
            $screenshot_image = $this->request->post("screenshot_image");
            $token = $this->request->post('__token__');
            $rule = [
                'association_name' => 'require',
                'amount' => 'require',
                'is_shared' => 'require',
                'screenshot_image' => 'require',
                '__token__' => 'token',
            ];

            $msg = [
            ];
            $data = [
                'association_name' => $association_name,
                'amount' => $amount,
                'is_shared' => $is_shared,
                'screenshot_image' => $screenshot_image,
                '__token__' => $token,
            ];
            $field = [
                'association_name' => __('社群名称'),
                'amount' => __('红包金额'),
                'screenshot_image' => __('红包截图'),
                'is_shared' => __('是否分享'),
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                return $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            Db::startTrans();
            try {
                unset($data['__token__']);
                $data['user_id'] = $this->auth->id;
                model('UserRedPacket')->save($data);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return $this->error($e->getMessage(), null, ['token' => $this->request->token()]);
            }
            return $this->success();
        } else {
            return $this->error('错误的请求', null, ['token' => $this->request->token()]);
        }
    }
}