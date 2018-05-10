<?php

namespace app\common\model;

use think\Model;

class Fishing extends Model
{
    // 表名
    protected $name = 'fishing';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'use_time_text',
        'get_time_text',
        'is_get_text'
    ];
    

    
    public function getIsGetList()
    {
        return ['4' => __('Is_get 4')];
    }     


    public function getUseTimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['use_time'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getGetTimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['get_time'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getIsGetTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['is_get'];
        $list = $this->getIsGetList();
        return isset($list[$value]) ? '收获' : '未收获';
    }

    protected function setUseTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setGetTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    /**
     * 查询用户目前钓鱼状态
     */
    public static function selectFishStatus($uid)
    {
        $model  = new self();
        $where  = [
            'uid'   => $uid
        ];
        $info   = $model->where($where)->order('id desc')->find();
        if((isset($info['is_get']) && $info['is_get'] == 1) || !$info)
        {//可以钓鱼
            return ['code'=>1];
        }
        else 
        {
            $expire = time() - $info['use_time'] - config('finish_time');
            if($expire > 0)
                return ['code'=>2];
            return ['code'=>3,'expire'=>abs($expire)];
        }
    }
    
    /**
     * 钓鱼
     */
    public static function pullfish($uid)
    {
        try {
        
        $model  = new self();
        $where  = [
            'uid'   => $uid
        ];
        $info   = $model->where($where)->order('id desc')->find();
        if((isset($info['is_get']) && $info['is_get'] == 1) || !$info)
        {//可以钓鱼
            
            $model->uid = $uid;
            $model->use_time = time();
            $model->use_date    = date('Y-m-d');
            $model->save();
            return 1;
        }
        else
        {
            return 2;
        }

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    
    
    /**
     * 收获
     */
    public static function getFish($uid)
    {
        $model  = new self();
        $where  = [
            'uid'   => $uid
        ];
        $info   = $model->where($where)->order('id desc')->find();
        if(isset($info['is_get']) && $info['is_get'] == 0)
        {
            if(time() - $info['use_time'] - config('finish_time') < 0)
            {
                return 2;
            }
            else
            {
                //插入详情
                $num    = ScoreLog::harvestSelf($uid);
                if(!$num)
                    return 4;
                $data   = [
                    'num'       => $num,
                    'get_time'  => time(),
                    'get_date'  => date('Y-m-d'),
                    'is_get'    => 1
                ];
                $model->get($info['id'])->save($data);
                return ['num'=>$num];
            }
            
        }
        else
        {
            return 3;
        }
    }
}
