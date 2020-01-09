<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Sms;
use app\common\model\UserAccountLog;
use app\common\model\UserRecharge;
use app\common\service\Helper;
use qrcode\QRcode;
use think\Config;
use think\Cookie;
use think\Db;
use think\Hook;
use think\Session;
use think\Validate;

/**
 * 会员中心
 */
class User extends Frontend
{
    protected $noNeedLogin = ['login', 'register', 'third'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $auth = $this->auth;

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

        //监听注册登录注销的事件
        Hook::add(
            'user_login_successed',
            function ($user) use ($auth) {
                $expire = input('post.keeplogin') ? 30 * 86400 : 0;
                Cookie::set('uid', $user->id, $expire);
                Cookie::set('token', $auth->getToken(), $expire);
            }
        );
        Hook::add(
            'user_register_successed',
            function ($user) use ($auth) {
                Cookie::set('uid', $user->id);
                Cookie::set('token', $auth->getToken());
            }
        );
        Hook::add(
            'user_delete_successed',
            function ($user) use ($auth) {
                Cookie::delete('uid');
                Cookie::delete('token');
            }
        );
        Hook::add(
            'user_logout_successed',
            function ($user) use ($auth) {
                Cookie::delete('uid');
                Cookie::delete('token');
            }
        );
    }

    /**
     * 空的请求
     * @param $name
     * @return mixed
     */
    public function _empty($name)
    {
        $data = Hook::listen("user_request_empty", $name);
        foreach ($data as $index => $datum) {
            $this->view->assign($datum);
        }
        return $this->view->fetch('user/' . $name);
    }

    /**
     * 会员中心
     */
    public function index()
    {
        $level = \app\common\model\User::LEVELS[$this->auth->level];
        $this->view->assign('level', $level);
        return $this->view->fetch();
    }

    /**
     * 注册会员
     */
    public function register($rid = 0)
    {
        $url = $this->request->request('url', '', 'trim');
        if ($this->auth->id) {
            $this->success(__('You\'ve logged in, do not login again'), $url ? $url : url('user/index'));
        }
        if ($this->request->isPost()) {
            $mobile = $this->request->post('mobile');
            $captcha = $this->request->post('captcha');
            $password = $this->request->post('password');
            $validate_password = $this->request->post('validate_password');
            $rMobile = $this->request->post('rmobile', '');
            $token = $this->request->post('__token__');
            $rule = [
                'mobile' => 'require|regex:/^1\d{10}$/',
                'password' => 'require|length:6,30',
                'validate_password' => 'require|length:6,30|confirm:password',
                'rmobile' => 'require|regex:/^1\d{10}$/',
                '__token__' => 'require|token',
            ];

            $msg = [
                'mobile.require' => 'Mobile can not be empty',
                'rmobile.require' => '推荐人不能为空',
                'password.require' => 'Password can not be empty',
                'password.length' => 'Password must be 6 to 30 characters',
            ];
            $data = [
                'mobile' => $mobile,
                'password' => $password,
                'rmobile' => $rMobile,
                'validate_password' => $validate_password,
                '__token__' => $token,
            ];

//            //手机验证码
//            $ret = Sms::check($mobile, $captcha, 'register');
//            if (!$ret) {
//                return $this->error(__('Captcha is incorrect'));
//            }
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                return $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            $rUser = \app\common\model\User::getByMobile($rMobile);
            if (empty($rUser)) {
                return $this->error('推荐人不存在', null, ['token' => $this->request->token()]);
            }
            //判断推荐人是否为有效小推荐人
            $rid = $rUser->id;
            $rUserRecharge = UserRecharge::where('user_id', $rid)->find();
            if (empty($rUserRecharge)) {
                return $this->error('无效的推荐人', null, ['token' => $this->request->token()]);
            }
            if ($this->auth->register($mobile, $password, $rid)) {
                return $this->success(__('Sign up successful'), $url ? $url : url('user/index'));
            } else {
                return $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        //判断来源
        $referer = $this->request->server('HTTP_REFERER');
        if (!$url && (strtolower(parse_url($referer, PHP_URL_HOST)) == strtolower($this->request->host()))
            && !preg_match("/(user\/login|user\/register|user\/logout)/i", $referer)) {
            $url = $referer;
        }
        $rUser = \app\common\model\User::find($rid);
        $this->view->assign('url', $url);
        $this->view->assign('rid', $rid);
        $this->view->assign('rMobile', $rUser->mobile ?? '');
        $this->view->assign('title', __('Register'));
        return $this->view->fetch();
    }

    /**
     * 会员登录
     */
    public function login()
    {
        $url = $this->request->request('url', '', 'trim');
        if ($this->auth->id) {
            $this->redirect(url('/index'));
        }
        if ($this->request->isPost()) {
            $account = $this->request->post('account');
            $password = $this->request->post('password');
            $keeplogin = (int)$this->request->post('keeplogin');
            $token = $this->request->post('__token__');
            $rule = [
                'account' => 'require|length:3,50',
                'password' => 'require|length:6,30',
                '__token__' => 'require|token',
            ];

            $msg = [
                'account.require' => 'Account can not be empty',
                'account.length' => 'Account must be 3 to 50 characters',
                'password.require' => 'Password can not be empty',
                'password.length' => 'Password must be 6 to 30 characters',
            ];
            $data = [
                'account' => $account,
                'password' => $password,
                '__token__' => $token,
            ];
            $field = [
                'account' => __('Account'),
                'password' => __('Password'),
            ];
            $rule['captcha'] = 'require|captcha';
            $data['captcha'] = $this->request->post('captcha');
            $field['captcha'] = '验证码';
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
                return false;
            }
            if ($this->auth->login($account, $password)) {
                $this->success();
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        //判断来源
        $referer = $this->request->server('HTTP_REFERER');
        if (!$url && (strtolower(parse_url($referer, PHP_URL_HOST)) == strtolower($this->request->host()))
            && !preg_match("/(user\/login|user\/register|user\/logout)/i", $referer)) {
            $url = $referer;
        }
        $this->view->assign('url', $url);
        $this->view->assign('title', __('Login'));
        return $this->view->fetch();
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        //注销本站
        $this->auth->logout();
        $this->redirect('/index/user/login');
    }

    /**
     * 个人信息
     */
    public function profile()
    {
        $qr_code = $this->auth->association && $this->auth->association[0]->status == 2 ? $this->auth->association[0]->qr_code : '';
        $this->view->assign('title', __('Profile'));
        $this->view->assign('qr_code', $qr_code);
        return $this->view->fetch();
    }

    /**
     * 修改密码
     */
    public function changepwd()
    {
        if ($this->request->isPost()) {
            $oldpassword = $this->request->post("oldpassword");
            $newpassword = $this->request->post("newpassword");
            $renewpassword = $this->request->post("renewpassword");
            $token = $this->request->post('__token__');
            $rule = [
                'oldpassword' => 'require|length:6,30',
                'newpassword' => 'require|length:6,30',
                'renewpassword' => 'require|length:6,30|confirm:newpassword',
                '__token__' => 'token',
            ];

            $msg = [
            ];
            $data = [
                'oldpassword' => $oldpassword,
                'newpassword' => $newpassword,
                'renewpassword' => $renewpassword,
                '__token__' => $token,
            ];
            $field = [
                'oldpassword' => __('Old password'),
                'newpassword' => __('New password'),
                'renewpassword' => __('Renew password')
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
                return false;
            }

            $ret = $this->auth->changepwd($newpassword, $oldpassword);
            if ($ret) {
                $this->success(__('Reset password successful'), url('user/login'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        $this->view->assign('title', __('Change password'));
        return $this->view->fetch();
    }

    /**
     * 安全中心
     * @return string
     * @throws \think\Exception
     */
    public function securityCenter()
    {
        return $this->view->fetch();
    }

    /**
     * 修改登录密码
     * @return string|void
     * @throws \think\Exception
     */
    public function loginPassword()
    {
        if ($this->request->isAjax()) {
            $mobile = $this->request->post("mobile");
            $password = $this->request->post("password");
            $ycode = $this->request->post("ycode");
            $token = $this->request->post('__token__');
            $rule = [
                'mobile' => 'require',
                'password' => 'require|length:6,30',
                '__token__' => 'token',
            ];

            $msg = [
            ];
            $data = [
                'mobile' => $mobile,
                'password' => $password,
                '__token__' => $token,
            ];
            $field = [
                'mobile' => __('手机号'),
                'password' => __('新密码'),
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                return $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            $ret = Sms::check($mobile, $ycode, 'changepwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            $ret = $this->auth->changepwd($password, '', true);
            if ($ret) {
                return $this->success(__('Reset password successful'));
            } else {
                return $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        return $this->view->fetch();
    }

    /**
     * 修改支付密码
     * @return string|void
     * @throws \think\Exception
     */
    public function payPassword()
    {
        if ($this->request->isAjax()) {
            $mobile = $this->request->post("mobile");
            $password = $this->request->post("password");
            $ycode = $this->request->post("ycode");
            $token = $this->request->post('__token__');
            $rule = [
                'mobile' => 'require',
                'password' => 'require|length:6,30',
                '__token__' => 'token',
            ];

            $msg = [
            ];
            $data = [
                'mobile' => $mobile,
                'password' => $password,
                '__token__' => $token,
            ];
            $field = [
                'mobile' => __('手机号'),
                'password' => __('新密码'),
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                return $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            $ret = Sms::check($mobile, $ycode, 'changepaypwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            $ret = $this->auth->changePayPassword($password);
            if ($ret) {
                return $this->success(__('Reset password successful'));
            } else {
                return $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        return $this->view->fetch();
    }

    /**
     * 我的直推
     * @return string
     * @throws \think\Exception
     */
    public function recommend()
    {
        $sql = 'SELECT ru.number,ru.mobile,ru.wechat 
                FROM qkl_user u 
                JOIN qkl_user_recommend ur ON u.id=ur.user_id JOIN qkl_user ru ON ur.referral_user_id=ru.id
                WHERE u.id=' . $this->auth->id .
            '   LIMIT 0 , 15';
        $recommends = Db::query($sql);
        $this->assign('recommends', $recommends);
        return $this->view->fetch();
    }

    /**
     * 我的分享
     * @return string
     * @throws \think\Exception
     */
    public function share()
    {
        $canShare = Helper::canShare($this->auth->id);
        if(!$this->auth->share_image || !is_file($this->auth->share_image)){
            $user = $this->auth->getUser();
            $user->share_image = $this->auth->share_image = Helper::generateShareImage($user->id);
            $user->save();
        }
        $this->assign('canShare', $canShare);
        return $this->view->fetch();
    }

    /**
     * 我的节点
     * @return array|string
     * @throws \think\Exception
     */
    public function node()
    {
        if ($this->request->isAjax()) {
            $treeData[] = [
                'id' => $this->auth->id,
                'pId' => 0,
                'name' => $this->auth->number,
                'open' => true,
            ];

            $treeData = array_merge($treeData, Helper::getNodes($this->auth->id));
            return $treeData;
        }
        return $this->view->fetch();
    }
}
