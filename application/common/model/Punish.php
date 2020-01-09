<?php

namespace app\common\model;

use think\Model;


class Punish extends Model
{



    protected static function init()
    {
        self::beforeInsert(function ($row){
            $userNotice = new Notices([
                'type'=>2,
                'user_id'=>$row->user_id,
                'title'=>'处罚通知',
                'content'=>'亲,您违反了'.$row->violate.'条例，需要惩罚'.($row->amount).'个USDT,请您及时处理',
            ]);
            $userNotice->save();
        });
    }

    // 表名
    protected $name = 'punish';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
    ];
    

    
    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function appeal()
    {
        return $this->hasOne('PunishAppeal');
    }
}
