<?php

namespace app\admin\model;

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
        'is_get_text',
        'is_pull_text'
    ];
    

    
    public function getIsGetList()
    {
        return ['4' => __('Is_get 4')];
    }     

    public function getIsPullList()
    {
        return ['4' => __('Is_pull 4')];
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
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsPullTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['is_pull'];
        $list = $this->getIsPullList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setUseTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setGetTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


}
