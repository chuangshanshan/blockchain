<?php


namespace app\index\controller;


use app\common\controller\Frontend;
use app\common\model\LearnHistory;
use app\common\model\MaterialCategory;

class Learn extends Frontend
{
    protected $layout = '';
    protected $noNeedRight = '*';

    /**
     * 学习首页
     */
    public function index($type = 1)
    {
        $materialCategories = model('MaterialCategory')->order('weight', 'desc')->column('id,name,type', 'id');
        $arr = [
            'video' => [],
            'audio' => [],
            'article' => [],
        ];
        foreach ($materialCategories as $materialCategory) {
            $arr[MaterialCategory::TYPES[$materialCategory['type']]][] = $materialCategory;
        }
        $materialCategories = $arr;
        $histories = model('LearnHistory')->with(['learn_material'])->where('user_id', $this->auth->id)->order(
            'learn_time',
            'desc'
        )->limit(50)->column(
            'learnMaterial.title,learnMaterial.id lmid,learnMaterial.type,learnMaterial.status,learn_history.learn_time',
            'learn_history.id'
        );
        foreach ($histories as $key => $history) {
            if (in_array($history['type'], array_keys(MaterialCategory::TYPES)) && $history['status'] == 1) {
                $histories[$key]['type'] = $history['type'] == 3 ? 'detail' : MaterialCategory::TYPES[$history['type']];
            } else {
                unset($histories[$key]);
            }
        }
        $this->assign('materialCategories', $materialCategories);
        $this->assign('histories', $histories);
        $this->assign('type', $type);
        return $this->view->fetch();
    }

    /**
     * 视频
     * @param int $category
     * @param int $id
     * @return string|void
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function video($category = 0, $id = 0)
    {
        $where = [];
        if ($category) {
            $where['material_category_id'] = $category;
        }
        if ($id) {
            $where['id'] = $id;
        }

        $videos = model('LearnMaterial')->field('id,file,title,description')->where('type', 1)->where($where)->where(
            'status',
            1
        )->order('create_time', 'desc')->paginate(5)->toArray();
        if ($this->request->isAjax()) {
            return $this->success('', null, $videos['data']);
        }
        $this->assign('videos', $videos);
        return $this->view->fetch();
    }


    /**
     * 音频
     * @param int $category
     * @param int $id
     * @return string|void
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function audio($category = 0, $id = 0)
    {
        $where = [];
        if ($category) {
            $where['material_category_id'] = $category;
        }
        if ($id) {
            $where['id'] = $id;
        }
        $audios = model('LearnMaterial')->where('type', 2)->where($where)->where('status', 1)->order(
            'create_time',
            'desc'
        )->field('id,file,title,description')->paginate(10)->toArray();
        if ($this->request->isAjax()) {
            return $this->success('', null, $audios['data']);
        }
        $this->assign('audios', $audios);
        return $this->view->fetch();
    }

    /**
     * 文章列表
     * @param int $category
     * @param int $id
     * @return string|void
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function article($category = 0, $id = 0)
    {
        $where = [];
        if ($category) {
            $where['material_category_id'] = $category;
        }
        if ($id) {
            $where['id'] = $id;
        }
        $articles = model('LearnMaterial')->where('type', 3)->where($where)->where('status', 1)->order(
            'create_time',
            'desc'
        )->field('id,title')->paginate(15)->toArray();
        if ($this->request->isAjax()) {
            return $this->success('', null, $articles['data']);
        }
        $this->assign('articles', $articles);
        return $this->view->fetch();
    }


    /**
     * 文章详情
     * @param $id
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($id)
    {
        $article = model('LearnMaterial')->findOrFail($id);
        $this->addHistory($id);
        $this->assign('article', $article);
        return $this->view->fetch();
    }


    /**
     * 添加历史
     */
    public function history()
    {
        if ($this->request->isAjax()) {
            $learn_material_id = $this->request->post('id');
            $this->addHistory($learn_material_id);
        }
        return $this->success('', null, ['token' => $this->request->token()]);
    }

    /**
     * 添加历史
     * @param $learn_material_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function addHistory($learn_material_id)
    {
        $isHistory = model('LearnHistory')->where('learn_material_id', $learn_material_id)->where(
            'user_id',
            $this->auth->id
        )->find();
        if ($isHistory) {
            return true;
        }
        $historyData = [
            'user_id' => $this->auth->id,
            'learn_material_id' => $learn_material_id,
            'learn_time' => time()
        ];
        model('LearnHistory')->insert($historyData);
    }
}