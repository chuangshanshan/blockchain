<?php

namespace app\common\model;

use think\Model;


class UserWithdraw extends Model
{

    

    const STATUS_INIT = 0;
    const STATUS_AUDIT_SUCCESS = 1;
    const STATUS_REQUEST_SUCCESS = 2;
    const STATUS_SUCCESS = 33;
    const STATUS_FAIL = 9;
    const STATUSES = [
        self::STATUS_INIT=>'待审核',
        self::STATUS_AUDIT_SUCCESS=>'审核成功',
        self::STATUS_REQUEST_SUCCESS=>'请求成功',
        self::STATUS_SUCCESS=>'成功',
        self::STATUS_FAIL=>'失败',
    ];
    // 表名
    protected $name = 'user_withdraw';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'traded_time_text',
        'status_text',
    ];
    

    



    public function getTradedTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['traded_time']) && $data['traded_time'] ? $data['traded_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getStatusTextAttr($value, $data)
    {
        return self::STATUSES[$data['status']] ?? '';
    }


    protected function setTradedTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
