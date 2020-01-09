<?php

namespace app\common\model;

use think\Model;


class LearnHistory extends Model
{

    

    

    // 表名
    protected $name = 'learn_history';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'learn_time_text'
    ];
    

    



    public function getLearnTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['learn_time']) ? $data['learn_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setLearnTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function learnmaterial()
    {
        return $this->belongsTo('LearnMaterial', 'learn_material_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
