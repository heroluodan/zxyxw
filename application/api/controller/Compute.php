<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 计算接口
 */
class Compute extends Api
{

    /**
     * 首页
     * 
     */
    public function index()
    {
        $arr= $this->auth->getUserinfo();
        $this->success('请求成功1',$arr);
    }

}
