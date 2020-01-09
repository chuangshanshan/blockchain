<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\service\Helper;
use think\Db;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Examination extends Backend
{

    /**
     * Examination模型对象
     * @var \app\common\model\Examination
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\Examination;

    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['user', 'examinationpaper'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['user', 'examinationpaper'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $row) {

                $row->getRelation('user')->visible(['mobile']);
                $row->getRelation('examinationpaper')->visible(['name', 'score']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function award($ids)
    {
        if ($this->request->isPost()) {
            $row = $this->request->post('row/a');
            Db::startTrans();
            try {
                $examination = $this->model->where('user_award_id', 0)->findOrFail($ids);
                $userAward = new \app\common\model\UserAward([
                    'user_id' => $examination->user_id,
                    'amount' => $row['amount'] * 100,
                    'remark' => $row['remark'],
                ]);
                $userAward->save();
                $userAwardId = $userAward->getLastInsID();
                Helper::changeUserBalance($userAward->user_id, $userAward->amount, 3, $userAward->remark, $userAwardId);
                //新增一条通知
                $userNotice = new \app\common\model\Notices([
                    'type' => 1,
                    'user_id' => $userAward->user_id,
                    'title' => '奖励通知',
                    'content' => '亲,您'.($row['remark']?:'考试优异，').'您得到' . (bcdiv($userAward->amount, 100, 2)) . '个USDT的奖励已到账，祝您生活愉快',
                ]);
                $userNotice->save();
                $examination->user_award_id = $userAwardId;
                $examination->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return $this->error($e->getMessage());
            }
            return $this->success();
        }
        return $this->view->fetch();
    }
}
