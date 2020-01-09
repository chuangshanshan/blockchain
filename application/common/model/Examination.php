<?php

namespace app\common\model;

use think\Model;


class Examination extends Model
{

    

    

    // 表名
    protected $name = 'examination';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'begin_time_text',
        'end_time_text',
        'award_text'
    ];

    protected $type = [
        'answer_results'    =>  'json',
    ];


    public function getBeginTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['begin_time']) ? $data['begin_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['end_time']) ? $data['end_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setBeginTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setEndTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function getAwardTextAttr($value, $data)
    {
        return isset($data['user_award_id']) && $data['user_award_id'] > 0 ? '已奖励' : '未奖励' ;
    }


    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function examinationpaper()
    {
        return $this->belongsTo('ExaminationPaper', 'examination_paper_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
