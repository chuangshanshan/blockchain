<?php

namespace app\common\model;

use think\Model;


class UserAccountLog extends Model
{


    const TYPE_RECHARGE = 1;
    const TYPE_WITHDRAW = 2;
    const TYPE_AWARD = 3;
    const TYPE_PUNISH = 4;
    const TYPES = [
        self::TYPE_RECHARGE => '充值',
        self::TYPE_WITHDRAW => '提现',
        self::TYPE_AWARD => '奖励',
        self::TYPE_PUNISH => '处罚',
    ];

    const STATUS_HANDING = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAIL = 3;
    const STATUSES = [
        self::STATUS_HANDING => '处理中',
        self::STATUS_SUCCESS => '成功',
        self::STATUS_FAIL => '失败',
    ];

    // 表名
    protected $name = 'user_account_log';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'trade_time_text',
        'status_text'
    ];


    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2'), '3' => __('Type 3'), '4' => __('Type 4')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTradeTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['trade_time']) ? $data['trade_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        return isset(self::STATUSES[$value]) ? self::STATUSES[$value] : '';
    }

    protected function setTradeTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function user()
    {
        return $this->belongsTo('User');
    }
}
