<?php


namespace app\common\service;
if(is_file('../vendor/qiniu/php-sdk/autoload.php')){
    require '../vendor/qiniu/php-sdk/autoload.php';
}
if(is_file('./vendor/qiniu/php-sdk/autoload.php')){
    require './vendor/qiniu/php-sdk/autoload.php';
}
//vendor('Qiniu.autoload');
use app\common\model\UserWithdraw;
use Qiniu\Auth as Qiniu;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use app\common\model\Config;
use app\common\model\Examination;
use app\common\model\ExaminationPaper;
use app\common\model\Notices;
use app\common\model\Punish;
use app\common\model\User;
use app\common\model\UserAccount;
use app\common\model\UserAccountLog;
use app\common\model\UserAward;
use app\common\model\UserRecharge;
use app\common\model\UserRecommend;
use app\common\model\UserRedPacket;
use qrcode\QRcode;
use think\Db;

class Helper
{
    public static function calculateScore(&$answers)
    {
        $questionIds = array_keys($answers);
        $questions = model('Questions')->whereIn('id', $questionIds)->column('score,answer', 'id');
        $totalScore = 0;
        foreach ($answers as $key => &$v) {
            if ($v == $questions[$key]['answer']) {
                $totalScore += $questions[$key]['score'];
            }
            $v = [
                'result' => $v,
                'answer' => $questions[$key]['answer'],
            ];
        }
        return $totalScore;
    }

    /**
     * 用户余额变更
     * @param $userId
     * @param $changeBalance
     * @param $type
     * @param string $remark
     * @param int $businessId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function changeUserBalance($userId, $changeBalance, $type, $remark = '', $businessId = 0)
    {
        $userAccount = UserAccount::where('user_id', $userId)->findOrFail();
        $beforeBalance = $userAccount->balance;
        $tradeNo = '';
        $amount = 0;
        $status = 2;
        $trade_time = time();
        switch ($type) {
            case 1://充值
                $tradeNo = self::generateTradeNO('CZ');
                $amount = $changeBalance;
                break;
            case 2://提现
                if ($userAccount->balance < $changeBalance) {
                    abort(401, '余额不足无法提现');
                }
                $tradeNo = self::generateTradeNO('TX');
                $amount = $changeBalance * -1;
                break;
            case 3://奖励
                $tradeNo = self::generateTradeNO('JL');
                $amount = $changeBalance;
                break;
            case 4://惩罚
                if ($userAccount->balance < $changeBalance) {
                    abort(401, '余额不足,请充值');
                }
                $tradeNo = self::generateTradeNO('CF');
                $amount = $changeBalance * -1;
                break;
            default:
                abort(401, '错误的业务类型');
        }
        Db::startTrans();
        try {
            if ($type != 1) {
                $userAccount->save(
                    [
                        'balance' => floatval(bcadd($userAccount->balance, $amount, 6))
                    ],
                    [
                        'user_id' => $userId,
                        'balance' => $userAccount->balance,
                    ]
                );
            }
            $userAccountLog = new UserAccountLog(
                [
                    'user_id' => $userId,
                    'business_id' => $businessId,
                    'type' => $type,
                    'trade_no' => $tradeNo,
                    'amount' => $changeBalance,
                    'before_balance' => $beforeBalance,
                    'status' => $status,
                    'remark' => $remark,
                    'trade_time' => $trade_time,
                ]
            );
            $userAccountLog->save();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            abort(401, $e->getMessage());
        }
    }

    public static function generateTradeNO($type)
    {
        return $type . date('YmdHis') . substr(microtime(), 2, 8) . mt_rand(1000, 9999);
    }

    public static function generateShareImage($rid)
    {
        $uploadDir = '/uploads/share/' . date('Ymd') . '/';
        $filePath = ROOT_PATH . '/public' . $uploadDir;
        $dirPath = rtrim($filePath, DS) . DS;
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }
        $filePath = $dirPath . md5($rid) . '.png';
        $contentUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/index/user/register?rid=' . $rid;
        QRcode::png($contentUrl, $filePath);
        return $uploadDir . md5($rid) . '.png';
    }

    public static function generateWalletQRCode($walletAddress)
    {
        if (!$walletAddress) {
            return '';
        }
        $uploadDir = '/uploads/wallet/' . date('Ymd') . '/';
        $filePath = ROOT_PATH . '/public' . $uploadDir;
        $dirPath = rtrim($filePath, DS) . DS;
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }
        $filePath = $dirPath . md5($walletAddress) . '.png';
        QRcode::png($walletAddress, $filePath);
        return $uploadDir . md5($walletAddress) . '.png';
    }


    public static function generateUserNO($userId)
    {
        return 'U' . date('ymdH') . str_pad($userId % 10000, 4, '0', STR_PAD_LEFT);
    }

    public static function getNodes($userId, $class = 0)
    {
        $sql = 'SELECT ru.number,ru.id 
                FROM qkl_user u 
                JOIN qkl_user_recommend ur ON u.id=ur.user_id JOIN qkl_user ru ON ur.referral_user_id=ru.id
                WHERE u.id=' . $userId;
        $children = Db::query($sql);
        $nodes = [];
        $class++;
        if ($children) {
            foreach ($children as $child) {
                $node = [
                    'id' => $child['id'],
                    'pId' => $userId,
                    'name' => $child['number'],
                ];
                if ($class == 1) {
                    $node['isParent'] = true;
                }
                $nodes[] = $node;
                if ($class < 2) {
                    $nodes = array_merge($nodes, self::getNodes($child['id'], $class));
                }
            }
        }
        return $nodes;
    }

    /**
     * 推荐奖励
     * @param $rechargeUserId
     * @param $rechargeAmount
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function rechargeAward($rechargeUserId)
    {
        //获取充值人的推荐人
        $userRecommend = UserRecommend::where('referral_user_id', $rechargeUserId)->find();
        if (empty($userRecommend)) {//没有推荐人
            return true;
        }
        $recommendUser = User::find($userRecommend->user_id);
        if (empty($recommendUser)) {//也许被删除了
            return true;
        }
        $recommends = UserRecommend::where('user_id', $userRecommend->user_id)->column('*', 'id');
        $referralUserIds = array_column($recommends, 'referral_user_id');
        $userRecharges = UserRecharge::whereIn('user_id', $referralUserIds)->field('min(id) mid ,user_id')->group(
            'user_id'
        )->order('mid', 'asc')->select();
        $i = 0;
        foreach ($userRecharges as $userRecharge) {
            $i++;
            if ($userRecharge['user_id'] == $rechargeUserId) {
                break;
            }
        }
        //获取配置确定经理金额
        $config = Config::whereIn('name', ['recharge_raward_1', 'recharge_raward_12_', 'recharge_raward_2_11'])->column(
            'value',
            'name'
        );
        if ($i >= 12) {
            $awardAmount = $config['recharge_raward_12_'] ?? 120;
        } elseif ($i < 12 && $i > 1) {
            $awardAmount = $config['recharge_raward_2_11'] ?? 50;
        } else {
            $awardAmount = $config['recharge_raward_1'] ?? 150;
        }

        //新增奖励记录
        $userAward = new UserAward(
            [
                'user_id' => $recommendUser->id,
                'amount' => $awardAmount,
                'status' => 1,
                'remark' => '推荐奖励',
            ]
        );
        $userAward->save();

        //新增奖励通知
        $notice = new Notices(
            [
                'type' => 1,
                'user_id' => $recommendUser->id,
                'title' => '奖励通知',
                'content' => '亲,您推荐的用户充值了，您得到' . $awardAmount . '个USDT的奖励已到账，祝您生活愉快',
            ]
        );
        $notice->save();

        //修改用户余额
        self::changeUserBalance($recommendUser->id, $awardAmount, 3, '推荐奖励');

        //生成用户考卷
        self::generateExaminationPaper($recommendUser->id);

        //修改推荐人等级
        self::changeReferrerLevel($recommendUser->id);
    }

    /**
     * 充值改变用户等级
     * @param User $user
     * @return bool
     */
    public static function changeLevel(User $user)
    {
        if ($user->level == User::LEVEL_AIDE) {//已经是最高级无需更改
            return true;
        }
        $userRecharg = UserRecharge::where(
            [
                'user_id' => $user->id,
                'status' => UserRecharge::STATUS_SUCCESS,
            ]
        )->find();
        if (empty($userRecharg)) {
            return;
        }
        $user->level = User::LEVEL_TEACHER;
        $user->save();
        return true;
    }

    public static function changeReferrerLevel($referrerId)
    {
        $referrer = User::find($referrerId);
        if ($referrer->level == User::LEVEL_AIDE) {//已经是最高级无需更改
            return true;
        }
        $recommendCount = Helper::getRecommendUserCount($referrerId);
        $validCount = $recommendCount['valid'] ?? 0;
        if ($validCount >= 50) {//小助手
            $referrer->level = User::LEVEL_AIDE;
        } elseif ($validCount >= 12)//校长
        {
            $referrer->level = User::LEVEL_PRINCIPAL;
        } elseif ($validCount >= 8) {//导师
            $referrer->level = User::LEVEL_TUTOR;
        } else {//老师
            $referrer->level = User::LEVEL_TEACHER;
        }
        $referrer->save();
    }

    public static function generateExaminationPaper($userId)
    {
        //查询是否已经生成试卷
        $userExaminationPaperCount = ExaminationPaper::where('user_id', $userId)->count();

        //根据推荐有效会员数来生成考卷
        $recommendUserCount = self::getRecommendUserCount($userId);

        $num = $recommendUserCount['valid'] > 14 ? (floor(($recommendUserCount['valid'] - 14) / 5) + 2) : floor($recommendUserCount['valid'] / 7);

        if ($num <= $userExaminationPaperCount) {//一次只生成一张考卷
            return false;
        }
        $examinationPaper = new ExaminationPaper(
            [
                'user_id' => $userId,
                'name' => '知识考核' . ($userExaminationPaperCount + 1)
            ]
        );
        $examinationPaper->save();
        return $examinationPaper;
    }

    public static function ExaminationRewardsAndPenalties(Examination $examination)
    {
        //判断答对提数
        if ($wrongResult = count($examination->answer_results) - $examination->score / 2) {
            //扣除对应USDT
            $amount = $wrongResult * 2;
            self::changeUserBalance($examination->user_id, $amount, 4, '考试答错扣币');
        } else {
            self::changeUserBalance($examination->user_id, 100, 3, '考试全对奖币');
        }
        return true;
    }

    public static function canWithdraw($userId)
    {
        //获取考试
        $examination = Examination::where('user_id', $userId)->where('end_time', '>', '0')->find();
        if (empty($examination)) {
            return false;
        }

        //钱包任务
        $userRedPacket = UserRedPacket::where('user_id', $userId)->where('audit_status', 1)->find();
        if (empty($userRedPacket)) {
            return false;
        }

        //罚款
        $punish = Punish::where('user_id', $userId)->whereIn('status', [1, 2])->find();
        if (!empty($punish)) {
            return false;
        }
        return true;
    }

    public static function canShare($userId)
    {
        $userRecharge = UserRecharge::where('user_id', $userId)->find();
        return empty($userRecharge) ? false : true;
    }

    /**
     * 获取推荐的会员数量
     * @param $userId
     */
    public static function getRecommendUserCount($userId){
        $total = 0;
        $valid = 0;
        $totalRecommendUsers = UserRecommend::where('user_id',$userId)->column('referral_user_id');
        if(!empty($totalRecommendUsers)){
            $total = count($totalRecommendUsers);
            $valid = UserRecharge::whereIn('user_id',$totalRecommendUsers)->field('count(id),user_id')->group('user_id')->count();
        }
        return compact('total','valid');
    }

    public static function uploadQiniu($file, $fileName)
    {
        $upload = \think\Config::get('upload')['qiniu'];
        $result = [
            'status' => true,
            'msg' => 'ok',
            'url' => '',
        ];
        try {
            $qiniu = new Qiniu($upload['access_key'], $upload['secret_Key']);
            $bucket = $qiniu->uploadToken($upload['bucket']);
            $uploadMgr = new UploadManager();
            list($ret, $err) = $uploadMgr->putFile($bucket, $fileName, $file);
            if ($err != null) {
                echo $err;
                $result['status'] = false;
                $result['msg'] = $err;
            } else {
                $result['url'] = 'http://q3oja40bh.bkt.clouddn.com/' . ($ret['key'] ?? '');
            }
        } catch (\Exception $e) {
            $result['status'] = false;
            $result['msg'] = $e->getTraceAsString();
        }
        return $result;
    }

    /**
     * 可提币数量
     * @param $userId
     */
    public static function canWithdrawAmount($userId)
    {
        $amount = 0;
        //计算用户可提币数量
        $userExaminations = Examination::where('user_id', $userId)->where('end_time', '>', 0)->column('score', 'id');
        if (!empty($userExaminations)) {
            foreach ($userExaminations as $examinationScore) {
                if ($examinationScore == 100) {
                    $amount += 500;
                } else {
                    $amount += (500 - (100 - $examinationScore));
                }
            }

            //获取已提币数量
            $historyWithdrawAmount = UserWithdraw::where('user_id', $userId)
                ->where('status',UserWithdraw::STATUS_SUCCESS)
                ->sum('amount');
            $amount -= $historyWithdrawAmount;
        }
        return $amount;
    }
}