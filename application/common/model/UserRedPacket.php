<?php

namespace app\common\model;

use think\Model;


class UserRedPacket extends Model
{

    

    

    // 表名
    protected $name = 'user_red_packet';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'is_shared_text',
        'amount_text',
    ];
    

    
    public function getIsSharedList()
    {
        return ['0' => __('Is_shared 0'), '1' => __('Is_shared 1')];
    }


    public function getIsSharedTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_shared']) ? $data['is_shared'] : '');
        $list = $this->getIsSharedList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getAmountTextAttr($value, $data)
    {
        $value = $value ? : (isset($data['amount']) ? bcdiv($data['amount'],100,2) : '0.00');
        return $value;
    }




    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
