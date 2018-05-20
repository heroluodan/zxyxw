<?php

namespace app\api\controller;

use app\common\controller\Api;
use fast\Random;
use app\api\model\UserAttach;
use app\common\model\ScoreLog;
use app\common\model\Fishing;
use app\common\model\User;
use think\Request;
use app\admin\model\UserCash;

/**
 * 首页接口
 */
class Index extends Api
{

    protected $noNeedLogin = ['*'];
    //protected $noNeedRight = ['*'];

    /**
     * 首页
     * 获取用户信息
     */
    public function index()
    {
        
        $userinfo   = $this->auth->getUserInfo();
        if($userinfo['score']<300)
            return $this->success("请写联系管理开通渔场",[],4);
        $return   = Fishing::selectFishStatus($this->uid);
        
        $expire = 0;
        switch ($return['code'])
        {
            case 1: $msg    = '可以钓鱼';
                break;
            case 2: 
                $msg    = '可以收获';
                break;
            case 3: $msg    = '不能收获';
                $expire = $return['expire'];
                break;
            default: $msg    = '系统错误';
                break;
        }
        $this->success($msg,['expire'=>$expire,'userinfo'=>$this->auth->getUserinfo()],$return['code']);
    }

    /**
     * 钓鱼
     */
    public function pullfish()
    {
        $code   = Fishing::pullfish($this->uid);
        switch ($code)
        {
            case 1: $msg    = '垂钓成功';
            break;
            case 2: $msg    = '垂钓失败';
            break;
            default: $msg    = '系统错误';
            break;
        }
        $this->success($msg,[],$code);
    }
    
    /**
     * 收获鱼
     */
    public function getfish()
    {
        $return  = Fishing::getFish($this->uid);
        $data   = [];
        switch ($return)
        {
            case 4: 
                $code   = 4;
                $msg    = '系统繁忙';
            break;
            case 2: 
                $code   = 2;
                $msg    = '时间未到,请等待';
            break;
            case 3: 
                $code   = 3;
                $msg    = '请先垂钓';
            break;
            default:
                $code   = 1;
                $data   = $return;
                $msg    = '收获成功';
            break;
        }
        $this->success($msg,$data,$code);
    }
    
    
    /**
     * 是否设置超级密码
     */
    public function issetSuper()
    {
        if(User::issetSuperPwd($this->uid))
            $this->success('超级密码已设置');
        $this->error('超级密码未设置');
    }
    
    
    /**
     * 设置超级密码
     */
    public function setSuper()
    {
        $superPwd   = $this->request->request('superPwd');
        if(!$superPwd)
            $this->error('请填写超级密码');
        if(User::setSuperPwd($this->uid,$superPwd))
            $this->success('设置成功');
        $this->error('设置失败');
    }
    
    /**
     * 获取好友的鱼数
     */
    public function getDownUser()
    {
        $data   = UserAttach::getDownUser($this->uid);
        if($data)
            $this->success('获取成功',$data);
        $this->error('暂无下级');
    }
    
    /**
     * 收获好友
     */
    public function getUserNum()
    {
        $friend = $this->request->request('friend');
        if(!$friend)
        {
            $result = ScoreLog::harvestFriend($this->uid);
        }
        else 
        {
            $result = ScoreLog::harvestFriend($this->uid,$friend);
        }
        $this->success($result['msg'],[],$result['code']);
        
    }
    
    
    /**
     * 用户实时信息
     */
    public function getUserInfo()
    {
        $data   = $this->auth->getUserinfo();
        
        if($data['score']<300)
        {
            $data['score'] = 0;
            $data['useScore'] = 0;   
        }
        else{
            
            $data['useScore']   = round($data['score'] - 300,2);
            $data['score']   = 300;
        }
        $this->success(__('获取成功'),$data);
    }
    
    /**
     * 提现
     */
    public function cash()
    {
        $num    = $this->request->request('num',0);
        $superPwd    = $this->request->request('superPwd');
        $alipay = $this->request->request('alipay');
        if(!User::checkSuperPwd($this->uid,$superPwd))
            return $this->error(__('超级密码错误'));
        $return     = ScoreLog::cash($this->uid, $num);
        if($return)
        {
            $data   = [
                'uid'   => $this->uid,
                'num'   =>  $num,
                'createtime' => time(),
                'updatetime' => time(),
                'createdate' => date('Y-m-d H:i:s'),
                'updatedate' => date('Y-m-d H:i:s'),
            ];
            if(UserCash::insert($data))
            {
                $useScore   = $this->auth->getUserinfo()['score']-300 - ($num+($num/10));
                return $this->success(__('申请成功'),['num'=>$useScore]);
            }
                
                
            return $this->success(__('申请失败'));
        }
    }
    
    
    /**
     * 获取利率
     */
    public function getIncome()
    {
        for($i=3;$i>=0;$i--)
        {
            $begin =   strtotime(date('Y-m-d 00:00:00',strtotime('-'.$i.' day')));
            $end =   strtotime(date('Y-m-d 23:59:59',strtotime('-'.$i.' day')));
            $data['date'][] = date('m-d',strtotime('-'.$i.' day'));
            $where  = [
                'user_id'   => $this->uid,
                'createtime'=> ['between',[$begin,$end]],
                'score'     => ['gt',0],
            ];
            $data['data'][]   = ScoreLog::where($where)->sum('score');
        }
        $data['rate']   = ['basic'=>(config('rate')*100).'%','new'=>(config('rate')*100).'%'];
        $this->success(__('获取成功'),$data);
        
    }
}
