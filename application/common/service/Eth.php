<?php


namespace app\common\service;


use app\common\model\ExtraRecharge;
use app\common\model\User;
use app\common\model\UserRecharge;
use app\common\model\UserRecommend;
use app\common\model\UserWithdraw;
use fast\Http;
use think\Db;
use think\Log;
use Web3\Web3;

class Eth
{
    private $web3 = null;
    private $personal = null;
    private $baseAddress = null;
    private $baseAddressKey = null;
    private $mergerAddress = null;
    private $contractAddress = null;
    protected static $instance = null;

    protected function __construct()
    {
        $this->web3 = new Web3('http://geth:KKdfs342BVDFgrter@geth.com');
        $this->baseAddress = config('eth.baseAddress');
        $this->baseAddressKey = config('eth.baseAddressKey');
        $this->mergerAddress = config('eth.mergerAddress');
        $this->contractAddress = config('eth.contractAddress');
    }

    public static function getInstance()
    {
        return self::$instance ?: new self();
    }

    /**
     * 测试,获取区块
     */
    public function test_etc()
    {
        $eth = $this->web3->eth;
        $endblock = '';
        $eth->blockNumber(
            function ($err, $block) {
                if ($err !== null) {
                }
                dump($block);
                die;
            }
        );//最新块
    }

    /**
     * 测试,获取区块
     */
    public function ethSendTransaction1($params)
    {
        $result = [
            'status' => true,
            'msg' => 'success',
            'hash' => '',
        ];
        $eth = $this->web3->eth;
        $eth->sendTransaction(
            $params,
            function ($err, $hashStr) use (&$result) {
                if ($err !== null) {
                    $result['status'] = false;
                    $result['msg'] = $err->getMessage();
                    return;
                }
                $result['hash'] = $hashStr;
            }
        );
        return $result;
    }



    /**
     * 测试,获取区块
     */
    public function accounts()
    {
        $eth = $this->web3->eth;
        $endblock = '';
        $eth->accounts(
            function ($err, $accounts) {
                if ($err !== null) {
                }
                dump($accounts);
            }
        );
    }


    /**
     * 获取新的eth一个钱包地址
     * @param int $uid
     * @return array
     */
    public function newAddress($uid = 1)
    {
        try {
            $personal = $this->web3->personal;
            $pwd = uniqid($uid);
            $address = '';
            $personal->newAccount(
                $pwd,
                function ($err, $account) use (&$address) {
                    if ($err !== null) {
                        $address = '';
                    }
                    $address = $account;
                }
            );
        } catch (\Exception $e) {
            Log::error('添加新钱包地址失败：用户ID: ' . $uid . ' 错误信息：' . $e->getTraceAsString());
        }
        return compact('address', 'pwd');
    }

    /**
     * 发送以太坊交易
     * @param $from
     * @param $to
     * @param $amount
     * @param $key
     * @return array
     */
    public function ethSendTransaction($from, $to, $amount, $key)
    {
        $amount = $amount * pow(10, 18);
        $amount_de = dechex($amount);//10进制转化成16进制
        $amount = '0x' . $amount_de;
        $params = [
            'from' => $from,
            'to' => $to,
            'value' => $amount,
        ];
        return $this->sendTransaction($params, $key);
    }

    /**
     * 发送代币交易
     * @param $from
     * @param $to
     * @param $amount
     * @param $key
     * @return array
     */
    public function ercSendTransaction($from, $to, $amount, $key)
    {
        $params = [
            'from' => $from,
            'to' => $this->contractAddress,
            'gas' =>'0x'.dechex(140000),
            'value' => '0x0',
            'data' => '0xa9059cbb'
                . str_pad(substr($to, 2), 64, '0', STR_PAD_LEFT)
                . str_pad(dechex($amount*pow(10,6)), 64, '0', STR_PAD_LEFT)
        ];
        return $this->sendTransaction($params, $key);
    }

    /**
     * 发送交易
     * @param $params
     * @param $key
     * @return array
     */
    public function sendTransaction($params, $key)
    {
        $result = [
            'status' => true,
            'msg' => 'success',
            'hash' => '',
        ];
        $personal = $this->web3->personal;
        $personal->sendTransaction(
            $params,
            $key,
            function ($err, $hashStr) use (&$result) {
                if ($err !== null) {
                    $result['status'] = false;
                    $result['msg'] = $err->getMessage();
                    return;
                }
                $result['hash'] = $hashStr;
            }
        );
        return $result;
    }

    /**
     * 查询用户代币余额
     * @param $address
     * @return int
     */
    public function call($address)
    {
        $callResult = [
            'status' => true,
            'msg' => 'success',
            'balance' => 0,
        ];
        $eth = $this->web3->eth;
        $params = [
            'from' => $address,
            'to' => $this->contractAddress,
            'data' => '0x70a08231000000000000000000000000' . substr($address, 2)
        ];
        $eth->call(
            $params,
            'latest',
            function ($err, $result) use (&$callResult) {
                if ($err !== null) {
                    $callResult['status'] = false;
                    $callResult['msg'] = $err->getMessage();
                    return;
                }
                $callResult['balance'] = (float)bcdiv(hexdec(substr($result, 2)), pow(10, 6), 6);
            }
        );
        return $callResult;
    }


    /**
     * 同步充值
     * @param User $user
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function syncRecharge(User $user)
    {
        $params = [
            'module' => 'account',
            'action' => 'tokentx',
            'address' => $user->wallet,
            'startblock' => 8175172,
            'endblock' => 999999999,
            'sort' => 'asc',
        ];
        //获取以太坊改地址代币交易记录
        $url = "http://api-cn.etherscan.com/api";
        $res = Http::post($url, $params);
        $result = json_decode($res, true);
//        $result = [
//            'status'=>1,
//            'result'=>[
//                [
//                    'blockNumber' => '2228258',//区块编号
//                    'timeStamp' => '1473433992',//区块时间戳
//                    'hash' => '0x5f2cd76fd3656686e356bc02cc91d8d0726a16936fd08e67ed30467053225a86',//交易哈希
//                    'nonce' => '10',//nonce值
//                    'blockHash' => '0x466dbced0a98e1b0490f787b51570850c8347960a4e92bd89acf85299e4cb9dc',//区块哈希值
//                    'from' => '0x4e83362442b8d1bec281594cea3050c8eb01311c',//发起账号
//                    'to' => '0xb63a04ff570b0c0f0e9a118dda7874fff9917aed',//接收账号
//                    'value' => '150000000',//交易金额
//                    'gas' => '1000000',//gas用量
//                    'gasPrice' => '20000000000',//gas价格
//                    'isError' => '0',//是否发生错误，0表示没有错误，1表示发生错误
//                    'txreceipt_status' => '',//交易收据状态
//                    'input' => '0x2ac9bf090000000000000000000000000000000000000000000000000000000000000002000000000000000000000000000000000000000000000005a34a38fc00a00000000000000000000000000000000000000000000000000005386e53c7de1e0000',//交易附加数据，16进制字符串编码
//                    'contractAddress' => '0xdac17f958d2ee523a2206206994597c13d831ec7',//合约地址
//                    'cumulativeGasUsed' => '684715',//区块累计交易用量
//                    'gasUsed' => '93657',//本交易的gas用量
//                    'confirmations' => '13',//交易确认数
//                ]
//            ],
//        ];

        if ($result['status'] != 1) {
            return;
        }
        $transfers = $result['result'] ?: [];
        if (!$transfers) {
            return;
        }
        //循环入充值表
        foreach ($transfers as $transfer) {
            $rechargeAmount = (float)bcdiv($transfer['value'], pow(10, 6), 6);
            if (
                $transfer['to'] == $user->wallet
                && $transfer['contractAddress'] == $this->contractAddress
                && $transfer['confirmations'] > 12
                && $rechargeAmount == 150
            ) {
                $where = [
                    'user_id' => $user->id,
                    'hash_key' => $transfer['hash'],
                ];
                if (!empty(UserRecharge::where($where)->find())) {
                    continue;
                }
                Db::startTrans();
                try {
                    $userRecharge = new UserRecharge(
                        [
                            'user_id' => $user->id,
                            'from' => $transfer['from'],
                            'hash_key' => $transfer['hash'],
                            'amount' => $rechargeAmount,
                            'status' => UserRecharge::STATUS_SUCCESS,
                        ]
                    );
                    $userRecharge->save();
                    //用户账户变动
                    Helper::changeUserBalance($user->id, $userRecharge->amount, 1, '充值', $userRecharge->id);
                    //充值等级变动
                    Helper::changeLevel($user);
                    //推荐奖励
                    Helper::rechargeAward($user->id);
                    Db::commit();
                } catch (\Exception $e) {
                    echo($e->getMessage());
                    Db::rollback();
                    Log::error('同步提现失败,失败原因：' . $e->getTraceAsString());
                    continue;
                }
            }
        }
    }

    /**
     * 代币归并转账
     */
    public function ercMerger()
    {
        //获取所有未归并的充值记录
        $userRecharges = UserRecharge::where('is_merger', 0)->select();
        //循环归并
        if (empty($userRecharges)) {
            return;
        }
        foreach ($userRecharges as $userRecharge) {
            $userAddress = $userRecharge->user->wallet;
            $userAddressKey = $userRecharge->user->wallet_pwd;
            //获取用户代币余额
            $ercBalanceResult = $this->getErcBalance($userAddress);
            if ($ercBalanceResult['status'] != 1) {
                Log::info('获取用户代币余额失败');
                continue;
            }
            $ercBalance = $ercBalanceResult['result'] / pow(10, 6);
            if ($ercBalance <= 0) {//余额不充足跳过
                continue;
            }
            //获取用户以太坊余额
            $ethBalanceResult = $this->getEthBalance($userAddress);
            if (!$ethBalanceResult['status']) {
                Log::info('获取用户以太坊余额失败：' . $ethBalanceResult['msg']);
                continue;
            }
            $ethBalance = $ethBalanceResult['balance'];
            if ($ethBalance < '0.00024') { //不足以支付手续费，主地址转手续费
                //获取主钱包以太坊余额
                $baseEthBalanceResult = $this->getEthBalance($this->baseAddress);
                if (!$baseEthBalanceResult['status']) {
                    Log::info('获取用户以太坊余额失败：' . $baseEthBalanceResult['msg']);
                    continue;
                }
                $baseEthBalance = $baseEthBalanceResult['balance'];
                if ($baseEthBalance < '0.00048') {//无法支付手续费
                    continue;
                }
                //转手续费
                $res = $this->ethSendTransaction(
                    $this->baseAddress,
                    $userAddress,
                    '0.00024',
                    $this->baseAddressKey
                );
                if ($res['status']) {
                    Log::info('归并：手续费转入申请成功,地址 ' . $userAddress . ' 返回信息:' . json_encode($res));
                } else {
                    Log::error('归并：手续费转入失败，地址：' . $userAddress . ' 失败原因：' . $res['msg']);
                }
                continue;
            } else {//归并
                if (!$this->mergerAddress) {
                    Log::error('归并：资金转入失败，地址：' . $userAddress . ' 失败原因：' . $res['msg']);
                    return false;
                }
                $res = $this->ercSendTransaction(
                    $userAddress,
                    $this->mergerAddress,
                    $userRecharge->amount,
                    $userAddressKey
                );
                if ($res['status']) {
                    $userRecharge->is_merger = 1;
                    $userRecharge->save();
                    Log::info('归并：资金转入成功 ' . $userAddress . ' 返回信息：' . json_encode($res));
                } else {
                    Log::error('归并：资金转入失败，地址：' . $userAddress . ' 失败原因：' . $res['msg']);
                }
            }
        }
    }

    /**
     *  获取用户代币余额
     * @param $address
     * @return int
     */
    public function getErcBalance($address)
    {
        $params = [
            'module' => 'account',
            'action' => 'tokenbalance',
            'address' => $address,
            'contractaddress' => $this->contractAddress,
            'tag' => 'latest',
        ];
        //获取以太坊改地址代币交易记录
        $url = "http://api-cn.etherscan.com/api";
        $res = Http::post($url, $params);
        $result = json_decode($res, true);
        return $result;
    }


    /**
     * 获取用户以太坊余额
     * @param $address
     * @return int
     */
    public function getEthBalance($address)
    {
        $params = [
            'module' => 'account',
            'action' => 'balance',
            'address' => $address,
            'tag' => 'latest',
        ];
        //获取以太坊改地址代币交易记录
        $url = "http://api-cn.etherscan.com/api";
        $res = Http::post($url, $params);
        $result = json_decode($res, true);
        if($result['status'] == 1){
            $result['balance'] = (float)bcdiv($result['result'],pow(10,18),18);
        }else{
            $result['status'] = false;
        }
        return $result;
//        $eth = $this->web3->eth;
//        $eth->getBalance(
//            $address,
//            function ($err, $balance) use (&$result) {
//                if ($err !== null) {
//                    $result['status'] = false;
//                    $result['msg'] = $err->getMessage();
//                    return;
//                }
//                $result['balance'] = (float)bcdiv($balance, pow(10, 18), 18);
//            }
//        );
//        return $result;
    }

    public function getTransactionReceipt($hash)
    {
        $eth = $this->web3->eth;
        $transactionResult = [
            'status' => true,
            'msg' => 'success',
            'result' => null
        ];
        $eth->getTransactionReceipt(
            $hash,
            function ($err, $result) use (&$transactionResult) {
                if ($err !== null) {
                    $transactionResult['status'] = false;
                    $transactionResult['msg'] = $err->getMessage();
                    return;
                }
                $transactionResult['result'] = $result;
            }
        );
        return $transactionResult;
    }


    public function getTransactionCount($address)
    {
        $eth = $this->web3->eth;
        $transactionResult = [
            'status' => true,
            'msg' => 'success',
            'counts' => 0
        ];

        $eth->getTransactionCount(
            $address,
            'latest',
            function ($err, $result) use (&$transactionResult) {
                if ($err !== null) {
                    $transactionResult['status'] = false;
                    $transactionResult['msg'] = $err->getMessage();
                    Log::info('获取交易记录失败：' . $err->getMessage());
                    return;
                }

                $transactionResult['counts'] = $result->toString();
            }
        );
        return $transactionResult;
    }

    public function getTransactionByHash($hash)
    {
        $eth = $this->web3->eth;
        $transactionResult = [
            'status' => true,
            'msg' => 'success',
            'result' => null
        ];

        $eth->getTransactionByHash(
            $hash,
            function ($err, $result) use (&$transactionResult) {
                if ($err !== null) {
                    $transactionResult['status'] = false;
                    $transactionResult['msg'] = $err->getMessage();
                    return;
                }
                $transactionResult['result'] = $result;
            }
        );
        return $transactionResult;
    }


    /**
     * 获取区块高度
     * @return int
     */
    public function getBlockNumber()
    {
        $eth = $this->web3->eth;
        $result = [
            'status' => true,
            'msg' => 'success',
            'blockNumber' => 0,
        ];
        $blockNumber = 0;
        $eth->blockNumber(
            function ($err, $number) use (&$result) {
                if ($err !== null) {
                    $result['status'] = false;
                    $result['msg'] = $err->getMessage();
                    return;
                }
                $result['blockNumber'] = $number->toString();
            }
        );
        return $result;
    }

    /**
     * 请求提币
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function requestWithdraw()
    {
        $withdraws = UserWithdraw::where('status', UserWithdraw::STATUS_AUDIT_SUCCESS)->select();
        if (empty($withdraws)) {
            return;
        }
        foreach ($withdraws as $withdraw) {
            Db::startTrans();
            try {
                $res = $this->ercSendTransaction(
                    $this->baseAddress,
                    $withdraw->wallet_address,
                    $withdraw->real_amount,
                    $this->baseAddressKey
                );
                if ($res['status']) {
                    $withdraw->hash_key = $res['hash'];
                    $withdraw->status = UserWithdraw::STATUS_REQUEST_SUCCESS;
                    $withdraw->save();
                    Db::commit();
                    Log::info('提币：申请成功 ' . $withdraw->wallet_address);
                } else {
                    Db::rollback();
                    Log::error('提币：申请失败，地址：' . $withdraw->wallet_address . ' 失败原因：' . $res['msg']);
                }
            } catch (\Exception $e) {
                Db::rollback();
                echo($e->getMessage());
                continue;
            }
        }
    }

    /**
     * 查询提币结果
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function queryWithdraw()
    {
        //获取所有提现未处理的交易
        $withdraws = UserWithdraw::where('status', UserWithdraw::STATUS_REQUEST_SUCCESS)->select();
        if (empty($withdraws)) {
            return;
        }
        foreach ($withdraws as $withdraw) {
            //通过hash获取订单
            $withdrawResult = $this->getTransactionReceipt($withdraw->hash_key);
            if (!$withdrawResult['status']) {
                Log::error('查询交易记录失败:' . $withdrawResult['msg']);
                continue;
            }
            $withdrawResult = $withdrawResult['result'];
            //判断状态
            if (!isset($withdrawResult->status) || hexdec($withdrawResult->status) != 1) {
                continue;
            }

            //获取区块高度
            $blockNumberResult = $this->getBlockNumber();
            if (!$blockNumberResult['status']) {
                Log::error('查询区块高度失败:' . $blockNumberResult['msg']);
                continue;
            }
            $blockNumber = $blockNumberResult['blockNumber'];
            //比较区块高度确定成功
            if ($blockNumber - hexdec($withdrawResult->blockNumber) < 12) {
                continue;
            }
            //交易成功
            Db::startTrans();
            try {
                Helper::changeUserBalance($withdraw->user_id, $withdraw->real_amount, 2, '提现');
                $withdraw->status = UserWithdraw::STATUS_SUCCESS;
                $withdraw->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                Log::error('提现处理失败：' . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * 同步额外充值
     * @param User $user
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function syncExtraRecharge(User $user)
    {
        $params = [
            'module' => 'account',
            'action' => 'tokentx',
            'address' => $user->wallet,
            'startblock' => 8175172,
            'endblock' => 999999999,
            'sort' => 'asc',
        ];
        //获取以太坊改地址代币交易记录
        $url = "http://api-cn.etherscan.com/api";
        $res = Http::post($url, $params);
        $result = json_decode($res, true);

        if ($result['status'] != 1) {
            return;
        }
        $transfers = $result['result'] ?: [];
        if (!$transfers) {
            return;
        }
        //循环入充值表
        foreach ($transfers as $transfer) {
            $rechargeAmount = (float)bcdiv($transfer['value'], pow(10, 6), 6);
            if (
                $transfer['to'] == $user->wallet
                && $transfer['contractAddress'] == $this->contractAddress
                && $transfer['confirmations'] > 12
                && $rechargeAmount != 150
            ) {

                $where = [
                    'user_id' => $user->id,
                    'hash' => $transfer['hash'],
                ];
                if (!empty(ExtraRecharge::where($where)->find())) {
                    continue;
                }
                Db::startTrans();
                try {
                    $extraRecharge = new ExtraRecharge(
                        [
                            'user_id' => $user->id,
                            'hash' => $transfer['hash'],
                            'from' => $transfer['from'],
                            'to' => $transfer['to'],
                            'value' => $rechargeAmount,
                        ]
                    );
                    $extraRecharge->save();
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    Log::error('同步额外充值失败,失败原因：' . $e->getMessage());
                    continue;
                }
            }
        }
    }


    /**
     * 额外充值代币归并转账
     */
    public function mergerExtraRecharge()
    {
        //获取所有未归并的充值记录
        $extraRecharges = ExtraRecharge::where('is_merger', 0)->select();
        //循环归并
        if (empty($extraRecharges)) {
            return;
        }
        foreach ($extraRecharges as $extraRecharge) {
            $userAddress = $extraRecharge->user->wallet;
            $userAddressKey = $extraRecharge->user->wallet_pwd;
            //获取用户代币余额
            $ercBalanceResult = $this->getErcBalance($userAddress);
            if ($ercBalanceResult['status'] != 1) {
                Log::info('获取用户代币余额失败');
                continue;
            }
            $ercBalance = $ercBalanceResult['result'] / pow(10, 6);
            if ($ercBalance < $extraRecharge->value) {//余额不充足跳过
                continue;
            }
            //获取用户以太坊余额
            $ethBalanceResult = $this->getEthBalance($userAddress);
            if (!$ethBalanceResult['status']) {
                Log::info('获取用户以太坊余额失败：' . $ethBalanceResult['msg']);
                continue;
            }
            $ethBalance = $ethBalanceResult['balance'];
            if ($ethBalance < '0.00024') { //不足以支付手续费，主地址转手续费
                //获取主钱包以太坊余额
                $baseEthBalanceResult = $this->getEthBalance($this->baseAddress);
                if (!$baseEthBalanceResult['status']) {
                    Log::info('获取用户以太坊余额失败：' . $baseEthBalanceResult['msg']);
                    continue;
                }
                $baseEthBalance = $baseEthBalanceResult['balance'];
                if ($baseEthBalance < '0.00048') {//无法支付手续费
                    continue;
                }
                //转手续费
                $res = $this->ethSendTransaction(
                    $this->baseAddress,
                    $userAddress,
                    '0.00024',
                    $this->baseAddressKey
                );
                if ($res['status']) {
                    Log::info('额外充值归并：手续费转入申请成功,地址 ' . $userAddress . ' 返回信息:' . json_encode($res));
                } else {
                    Log::error('额外充值归并：手续费转入失败，地址：' . $userAddress . ' 失败原因：' . $res['msg']);
                }
                continue;
            } else {//归并
                if (!$this->mergerAddress) {
                    Log::error('额外充值归并：资金转入失败，地址：' . $userAddress . ' 失败原因：' . $res['msg']);
                    return false;
                }
                $res = $this->ercSendTransaction(
                    $userAddress,
                    $this->mergerAddress,
                    $extraRecharge->value,
                    $userAddressKey
                );
                if ($res['status']) {
                    $extraRecharge->is_merger = 1;
                    $extraRecharge->save();
                    Log::info('额外充值归并：资金转入成功 ' . $userAddress . ' 返回信息：' . json_encode($res));
                } else {
                    Log::error('额外充值归并：资金转入失败，地址：' . $userAddress . ' 失败原因：' . $res['msg']);
                }
            }
        }
    }
}
