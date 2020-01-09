<?php

namespace app\common\model;

use think\Model;


class Questions extends Model
{

    
    const TYPE_CHOICE = 1;
    const TYPE_TRUE_OR_FALSE = 2;


    // 表名
    protected $name = 'questions';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'answer_text',
        'complexity_text'
    ];
    

    
    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2')];
    }

    public function getAnswerList()
    {
        return ['A' => __('Answer A'), 'B' => __('Answer B'), 'C' => __('Answer C'), 'D' => __('Answer D')];
    }

    public function getComplexityList()
    {
        return ['1' => __('Complexity 1'), '2' => __('Complexity 2'), '3' => __('Complexity 3')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getAnswerTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['answer']) ? $data['answer'] : '');
        $list = $this->getAnswerList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getComplexityTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['complexity']) ? $data['complexity'] : '');
        $list = $this->getComplexityList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
