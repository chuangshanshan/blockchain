<?php


namespace app\common\service;


use app\common\model\Notices;
use app\common\model\UserAward;

class Award
{
    public static function registerAward($registerId,$referrerId)
    {
        //推荐人奖励
        $referrerAward = self::awardUser($referrerId,10,'推荐成功','推荐成功');
        //注册人奖励
        $registerAward = self::awardUser($registerId,20,'注册奖励','注册成功');
    }

    public static function awardUser($userId, $amount, $remark, $noticeContent)
    {
        $userAward = new UserAward(
            [
                'user_id' => $userId,
                'amount' => $amount,
                'status' => 1,
                'remark' => $remark,
            ]
        );
        $userAward->save();
        //新增奖励通知
        $notice = new Notices(
            [
                'type' => 1,
                'user_id' => $userId,
                'title' => '奖励通知',
                'content' => '亲,您' . $noticeContent . '，您得到' . $amount . '个USDT的奖励已到账，祝您生活愉快',
            ]
        );
        $notice->save();

        //修改用户余额
        Helper::changeUserBalance($userId, $amount, 3, $remark);
    }
}