<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class PunishAppeal extends Backend
{
    
    /**
     * PunishAppeal模型对象
     * @var \app\common\model\PunishAppeal
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\PunishAppeal;
        $this->view->assign("statusList", $this->model->getStatusList());
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
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['user','punish'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['user','punish'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                
                $row->getRelation('user')->visible(['mobile']);
				$row->getRelation('punish')->visible(['title']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 详情
     * @param $ids
     * @return string|void
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function detail($ids)
    {
        $row = $this->model->get(['id' => $ids]);
        if($this->request->isPost()){
            $status = $this->request->post('status');
            Db::startTrans();
            try{
                if($status==2){//同意申诉，不在惩罚
                    $punish = model('Punish')->findOrFail($row->punish_id);
                    $punish->status = 3;
                    $punish->save();
                    $row->status = $status;
                    $row->save();
                }elseif($status == 3){//拒绝申诉
                    $row->status = $status;
                    $row->save();
                }else{
                    abort(400,'错误的请求');
                }
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
            }
            return  $this->success();
        }
        if (!$row)
            $this->error(__('No Results were found'));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
