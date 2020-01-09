<?php


namespace app\index\controller;


use app\common\controller\Frontend;
use app\common\model\UserAccountLog;
use app\common\model\UserWalletAddress;
use app\common\model\UserWithdraw;
use app\common\service\Eth;
use app\common\service\Helper;
use think\Db;
use think\Validate;

class Account extends Frontend
{
    protected $noNeedRight = '*';
    protected $layout = '';

    /**
     * 我的钱包
     * @return string
     * @throws \think\Exception
     */
    public function index()
    {
        $accountLog = [
            '1' => '0.00',
            '2' => '0.00',
            '3' => '0.00',
            '4' => '0.00',
        ];
        $userAccountLog = model('UserAccountLog')->where('user_id', $this->auth->id)->group('type')->column(
            'sum(amount)',
            'type'
        );
        $userAccountLog += $accountLog;
        $this->view->assign('userAccountLog', $userAccountLog);
        return $this->view->fetch();
    }

    /**
     * 账户流水
     * @param $type
     * @return string|void
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function log($type)
    {
        $userAccountLogs = model('UserAccountLog')->where('user_id', $this->auth->id)->where('type', $type)->where(
            'status',
            2
        )->order('createtime', 'desc')->field('id,amount,before_balance,trade_time')->paginate(15)->toArray();
        foreach ($userAccountLogs['data'] as &$accountLog) {
            $accountLog['trade_time'] = date('y/m/d H:i', $accountLog['trade_time']);
        }
        if ($this->request->isAjax()) {
            return $this->success('', null, $userAccountLogs['data']);
        }
        $title = UserAccountLog::TYPES[$type] . '记录';
        $logAccountTitle = $type == 3 ? '奖励金额' : ($type == 4 ? '处罚金额' : '变更金额');
        $this->assign('userAccountLogs', $userAccountLogs);
        $this->assign('title', $title);
        $this->assign('logAccountTitle', $logAccountTitle);
        return $this->view->fetch();
    }

    /**
     * 充值
     * @return string
     * @throws \think\Exception
     */
    public function recharge()
    {
        if (!$this->auth->wallet) {//未生成钱包补一下
            $user = $this->auth->getUser();
            $wallet = Eth::getInstance()->newAddress($this->auth->id);
            $user->wallet = $this->auth->wallet = $wallet['address'];
            $user->wallet_qrcode = $this->auth->wallet_qrcode = Helper::generateWalletQRCode($this->auth->wallet);
            $user->wallet_pwd = $wallet['pwd'];
            $user->save();
        }
        return $this->view->fetch();
    }


    /**
     * 申请提现
     * @param string $walletAddress
     * @return string|void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdraw($walletAddress = '')
    {
        //判断是否满足提现条件
        if (!Helper::canWithdraw($this->auth->id)) {
            $this->assign('canWithdraw', false);
            return $this->view->fetch();
        }

        $canWithdrawAmount = Helper::canWithdrawAmount($this->auth->id);

        if ($this->request->isPost()) {
            $amount = $this->request->post('amount');
            $pay_password = $this->request->post('pay_password');
            $address = $this->request->post('address');
            $token = $this->request->post('__token__');
            $rule = [
                'amount' => 'require|float',
                'pay_password' => 'require|length:6,30',
                'address' => 'require',
                '__token__' => 'token',
            ];

            $msg = [
                'mobile.require' => 'Mobile can not be empty',
                'password.require' => 'Password can not be empty',
                'password.length' => 'Password must be 6 to 30 characters',
            ];
            $data = [
                'amount' => $amount,
                'pay_password' => $pay_password,
                'address' => $address,
                '__token__' => $token,
            ];
            $field = [
                'amount' => __('提币数量'),
                'pay_password' => __('支付密码'),
                'address' => __('提币钱包地址'),
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                return $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }

            if (!$this->auth->pay_password) {
                return $this->error('未设置交易密码', null, ['token' => $this->request->token()]);
            }

            //检查支付密码
            if ($this->auth->pay_password != $this->auth->getEncryptPassword($pay_password, $this->auth->pay_salt)) {
                return $this->error('支付密码错误', null, ['token' => $this->request->token()]);
            }

            //获取用户是否有正在处理的提现的订单
            $handleWithdraws = UserWithdraw::where('user_id', $this->auth->id)->whereIn(
                'status',
                [
                    UserWithdraw::STATUS_INIT,
                    UserWithdraw::STATUS_AUDIT_SUCCESS,
                    UserWithdraw::STATUS_REQUEST_SUCCESS
                ]
            )->select();
            if (!empty($handleWithdraws)) {
                return $this->error('有未处理的提币申请，无法提币', null, ['token' => $this->request->token()]);
            }

            if ($amount > $canWithdrawAmount) {
                return $this->error('超过可提币数量，无法提币', null, ['token' => $this->request->token()]);
            }

            //获取系统配置
            $config = model('Config')->whereIn('name', ['withdraw_fee_rate', 'withdraw_lower_limit'])->column(
                'value',
                'name'
            );
            if (isset($config['withdraw_lower_limit']) && $amount < $config['withdraw_lower_limit']) {
                return $this->error('提币数量小于最小提币数量', null, ['token' => $this->request->token()]);
            }

            $withdraw_fee_rate = $config['withdraw_fee_rate'] ?? '0';
            $fee = (float)(bcdiv(bcmul($amount, $withdraw_fee_rate, 6), 100, 8));
            $real_amount = (float)bcsub($amount, $fee, 8);
            Db::startTrans();
            try {
                $userWithdraw = new UserWithdraw(
                    [
                        'user_id' => $this->auth->id,
                        'wallet_address' => $address,
                        'amount' => $amount,
                        'fee_rate' => $withdraw_fee_rate,
                        'fee' => $fee,
                        'real_amount' => $real_amount,
                        'status' => UserWithdraw::STATUS_INIT,
                    ]
                );
                $userWithdraw->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return $this->error($e->getMessage(), null, ['token' => $this->request->token()]);
            }
            return $this->success();
        }
        if (!$walletAddress) {
            $walletAddress = model('UserWalletAddress')->where('user_id', $this->auth->id)
                ->where('is_default', 1)
                ->column('wallet_address');
            if (!$walletAddress) {
                $walletAddress = model('UserWalletAddress')->where('user_id', $this->auth->id)->column(
                    'wallet_address'
                );
            }
            $walletAddress = $walletAddress ? $walletAddress[0] : '';
        }
        $this->assign('canWithdraw', true);
        $this->assign('canWithdrawAmount', $canWithdrawAmount);
        $this->assign('walletAddress', $walletAddress);
        return $this->view->fetch();
    }

    /**
     * 提现地址
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function walletAddress()
    {
        $walletAddresses = model('UserWalletAddress')->where('user_id', $this->auth->id)->order(
            'createtime',
            'desc'
        )->select();
        $this->assign('walletAddresses', $walletAddresses);
        return $this->view->fetch();
    }

    /**
     * 添加钱包地址
     * @return string|void
     * @throws \think\Exception
     */
    public function addWallet()
    {
        if ($this->request->isPost()) {
            $walletName = $this->request->post('walletName');
            $walletAddress = $this->request->post('walletAddress');
            $isDefault = $this->request->post('isDefault');
            if (preg_match('/^0x[a-fA-F0-9]{40}$/', $walletAddress) < 1) {
                return $this->error('提币地址格式错误', null, ['token' => $this->request->token()]);
            }
            if($walletAddress == $this->auth->wallet){
                return $this->error('提币地址不能是充币地址', null, ['token' => $this->request->token()]);
            }
            if ($isDefault) {
                model('UserWalletAddress')->where('user_id', $this->auth->id)->update(['is_default' => 0]);
            }
            $userWalletAddress = new UserWalletAddress(
                [
                    'user_id' => $this->auth->id,
                    'wallet_name' => $walletName,
                    'wallet_address' => $walletAddress,
                    'is_default' => $isDefault,
                ]
            );
            $userWalletAddress->save();
            return $this->success();
        }
        return $this->view->fetch();
    }

}