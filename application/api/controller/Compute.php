<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\ScoreLog;
use app\common\model\User;

/**
 * 计算接口
 */
class Compute extends Api
{

    /**
     * 转账
     * 
     */
    public function tradeToScore()
    {
        $superPwd   = $this->request->request('superPwd','');
        $to_id      = $this->request->request('to_id','');
        $num        = intval($this->request->request('num',''));
        $uid        = $this->auth->getUserinfo()['id'];
        
        if(!$superPwd || !$to_id || !$num)
            return $this->error('参数错误');
        
        $to = user::get(['mobile'=>$to_id])->id;
        if(!$to)
            return $this->error('用户不存在');
        
        //检查超级密码是否正确
        if(!User::checkSuperPwd($uid,$superPwd))
            return $this->error('超级密码错误');
            
        if(ScoreLog::userTrade($uid, $to, $num))
            $this->success('转账成功');
    }

}
