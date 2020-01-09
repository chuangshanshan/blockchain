<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\User;
use think\Config;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        $seventtime = \fast\Date::unixtime('day', -7);
        $paylist = $createlist = [];
        for ($i = 0; $i < 7; $i++)
        {
            $day = date("Y-m-d", $seventtime + ($i * 86400));
            $createlist[$day] = mt_rand(20, 200);
            $paylist[$day] = mt_rand(1, mt_rand(1, $createlist[$day]));
        }
        $hooks = config('addons.hooks');
        $uploadmode = isset($hooks['upload_config_init']) && $hooks['upload_config_init'] ? implode(',', $hooks['upload_config_init']) : 'local';
        $addonComposerCfg = ROOT_PATH . '/vendor/karsonzhang/fastadmin-addons/composer.json';
        Config::parse($addonComposerCfg, "json", "composer");
        $config = Config::get("composer");
        $addonVersion = isset($config['version']) ? $config['version'] : __('Unknown');
        $totalUser = User::count();
        $totalReport = \app\common\model\Report::where('createtime','>',strtotime(date('Y-m-d')))->count();
        $totalFeedback = \app\common\model\Feedback::where('createtime','>',strtotime(date('Y-m-d')))->count();
        $totalRecharge = \app\common\model\UserRecharge::where('status',\app\common\model\UserRecharge::STATUS_SUCCESS)->sum('amount');
        $todayRecharge = \app\common\model\UserRecharge::where('createtime','>',strtotime(date('Y-m-d')))->where('status',\app\common\model\UserRecharge::STATUS_SUCCESS)->sum('amount');
        $totalWithdraw = \app\common\model\UserWithdraw::where('status',\app\common\model\UserWithdraw::STATUS_SUCCESS)->sum('real_amount');
        $todayWithdraw = \app\common\model\UserWithdraw::where('traded_time','>',strtotime(date('Y-m-d')))->where('status',\app\common\model\UserWithdraw::STATUS_SUCCESS)->sum('real_amount');
        $totalAppeal = \app\common\model\PunishAppeal::where('status',1)->count();
        $this->view->assign([
            'totaluser'        => $totalUser,
            'totalviews'       => $totalReport,
            'totalorder'       => $totalFeedback,
            'totalRecharge' => $totalRecharge,
            'todayRecharge' => $todayRecharge,
            'totalWithdraw' => $totalWithdraw,
            'todayWithdraw' => $todayWithdraw,
            'totalAppeal' => $totalAppeal,
            'todayuserlogin'   => 321,
            'todayusersignup'  => 430,
            'todayorder'       => 2324,
            'unsettleorder'    => 132,
            'sevendnu'         => '80%',
            'sevendau'         => '32%',
            'paylist'          => $paylist,
            'createlist'       => $createlist,
            'addonversion'       => $addonVersion,
            'uploadmode'       => $uploadmode
        ]);

        return $this->view->fetch();
    }

}
