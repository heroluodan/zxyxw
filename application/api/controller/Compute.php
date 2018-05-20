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
        $to_id      = $this->request->request('toId','');
        $num        = intval($this->request->request('num',''));
        $uid        = $this->auth->getUserinfo()['id'];
        
        if(!$superPwd || !$to_id || !$num)
            $this->error('参数错误');
        $model  = user::get(['mobile'=>$to_id]);
        if(!$model)
            return $this->error(__('用户不存在'));
        $to = $model->id;
        if(!$to)
            $this->error('用户不存在');
        
        //检查超级密码是否正确
        if(!User::checkSuperPwd($uid,$superPwd))
            $this->error('超级密码错误');
        $return  = ScoreLog::userTrade($uid, $to, $num); 
        switch ($return)
        {
            case 1: $msg    = '转账成功';
            break;
            case 2: $msg    = '可转鱼数不足';
            break;
            default: $msg    = '系统错误';
            break;
        }
        $this->success($msg,[],$return);
    }

}
