<?php

namespace app\api\controller;

use app\common\controller\Api;
use fast\Random;
use app\api\model\UserAttach;
use app\common\model\ScoreLog;

/**
 * 首页接口
 */
class Index extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     * 
     */
    public function index()
    {
        //dump(ScoreLog::harvestSelf($this->auth->getUserinfo()['id']));exit;
//         dump(UserAttach::getDownUser());
        $this->success('请求成功',UserAttach::getDownUser());
    }

}
