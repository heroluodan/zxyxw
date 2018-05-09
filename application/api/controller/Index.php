<?php

namespace app\api\controller;

use app\common\controller\Api;
use fast\Random;
use app\api\model\UserAttach;
use app\common\model\ScoreLog;
use app\common\model\Fishing;

/**
 * 首页接口
 */
class Index extends Api
{

    //protected $noNeedLogin = ['*'];
    //protected $noNeedRight = ['*'];

    /**
     * 首页
     * 获取用户信息
     */
    public function index()
    {
        $userinfo   = $this->auth->getUserInfo();
        $return   = Fishing::selectFishStatus($this->uid);
        $expire = 0;
        switch ($return['code'])
        {
            case 1: $msg    = '可以钓鱼';
                break;
            case 2: 
                $msg    = '可以收获';
                $expire = $return['expire'];
                break;
            case 3: $msg    = '不能收获';
                break;
            default: $msg    = '系统错误';
                break;
        }
        $this->success($msg,['user'=>$userinfo,'expire'=>$expire],$return['code']);
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
}
