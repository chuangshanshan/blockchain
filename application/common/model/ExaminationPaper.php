<?php

namespace app\common\model;

use app\admin\model\Admin;
use think\Model;


class ExaminationPaper extends Model
{


    // 表名
    protected $name = 'examination_paper';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];

    protected $type = [
        'questions' => 'array',
    ];

    protected static function init()
    {
        self::beforeInsert(function ($row) {
            $choiceQuestionsNum = $row->choice_questions??0;
            $trueOrFalseQuestionsNum = $row->true_or_false_questions??0;
            if($choiceQuestionsNum == 0 && $trueOrFalseQuestionsNum == 0){
                $rangQuestions = Questions::where('score',2)->orderRaw('rand()')->limit(50)->column('score,complexity', 'id');
                $questions = array_keys($rangQuestions);
                $totalScore = array_sum(array_column($rangQuestions,'score'));
                $complexity = bcdiv((array_sum(array_column($rangQuestions,'complexity'))), (count($rangQuestions) * 3), 2) * 10;
            }else{
                $choice_questions = [];
                $true_or_false_questions = [];
                $choice_questions_scores = 0;
                $choice_questions_complexity = 0;
                $true_or_false_questions_scores = 0;
                $true_or_false_questions_complexity = 0;
                if ($row->choice_questions > 0) {
                    $choice_questions = model('questions')->where('type', Questions::TYPE_CHOICE)->where('score',2)->orderRaw('rand()')->limit($row->choice_questions)->column('score,complexity', 'id');
                    $choice_questions_scores = array_sum(array_column($choice_questions, 'score'));
                    $choice_questions_complexity = array_sum(array_column($choice_questions, 'complexity'));
                }
                if ($row->true_or_false_questions > 0) {
                    $true_or_false_questions = model('Questions')->where('type', Questions::TYPE_TRUE_OR_FALSE)->where('score',2)->orderRaw('rand()')->limit($row->true_or_false_questions)->column('score,complexity', 'id');
                    $true_or_false_questions_scores = array_sum(array_column($true_or_false_questions, 'score'));
                    $true_or_false_questions_complexity = array_sum(array_column($true_or_false_questions, 'complexity'));
                }
                $complexity = bcdiv(($choice_questions_complexity + $true_or_false_questions_complexity), ((count($choice_questions) + count($true_or_false_questions)) * 3), 2) * 10;
                $questions = array_keys($choice_questions + $true_or_false_questions);
                $totalScore = $choice_questions_scores + $true_or_false_questions_scores;
            }

            sort($questions);
            $row->questions = $questions;
            $row->score = $totalScore;
            $row->admin_id = session('admin.id') ?? 1;
            $row->complexity = $complexity;
            $row->duration = $row->duration ?? 60 * 60;
        });
    }


    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function examination()
    {
        return $this->hasManyThrough('Examination', 'User');
    }

    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin');
    }
}
