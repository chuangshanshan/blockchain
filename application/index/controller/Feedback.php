<?php


namespace app\index\controller;


use app\common\controller\Frontend;
use think\Db;
use think\Validate;

class Feedback extends Frontend
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    /**
     * 反馈
     * @return string|void
     * @throws \think\Exception
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $content = $this->request->post("content");
            $contact = $this->request->post("contact");
            $token = $this->request->post('__token__');
            $rule = [
                'content' => 'require',
                'contact' => 'require',
                '__token__' => 'token',
            ];

            $msg = [
            ];
            $data = [
                'content' => $content,
                'contact' => $contact,
                '__token__' => $token,
            ];
            $field = [
                'content' => __('反馈内容'),
                'contact' => __('联系内容'),
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                return $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            Db::startTrans();
            try {
                //新增举报记录
                $feedbackData = [
                    'content' => $content,
                    'contact' => $contact,
                ];
                $feedback = new \app\common\model\Feedback($feedbackData);
                $feedback->save();
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