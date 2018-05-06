<?php

namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 会员积分日志模型
 */
class ScoreLog Extends Model
{

    // 表名
    protected $name = 'user_score_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = '';
    //保底数
    protected $freeze = null;
    //扣费比例
    protected $proportion = null;
    
    protected $tradeType    = [
        'trade_push'    => '交易转账转出',
        'trade_get'     => '交易转账获得',
        'harvest_self'       => '鱼塘收获所得',
        'harvest_friend'       => '好友处收获',
    ];
    // 追加属性
    protected $append = [
    ];
    
    public function __construct($data="")
    {
        parent::__construct($data);
        $this->freeze = config('freeze');
        $this->proportion   = config('proportion');
    }
    
    /**
     * 获取汇率
     */
    public function getRate()
    {
        return config('rate');
    }
    
    
    /**
     * 用户转账
     * @param int $from
     * @param int $to
     * @param int $num
     */
    public static function userTrade($from,$to,$num)
    {
        $model  = new self();
        $from_score = User::where(['id'=>$from])->value('score');
        //手续费
        $poundage   = round($num * $model->proportion);
        $after  = $from_score - $model->freeze - $poundage;
        if($after >= $num)
        {
            $to_score = User::where(['id'=>$to])->value('score');
            $data   = [
                [
                    'user_id'   => $from,
                    'score'     => $num + $poundage,
                    'to'        => $to,
                    'poundage'  => $poundage,
                    'before'    => $from_score,
                    'after'     => $after,
                    'memo'      => $model->tradeType['trade_push'],
                ],
                [
                    'user_id'   => $to,
                    'score'     => $num,
                    'from'      => $from,
                    'before'    => $to_score,
                    'after'     => $to_score + $num,
                    'memo'      => $model->tradeType['trade_get'],
                ],
            ];
            Db::startTrans();
            try {
                Db::commit();
                User::get($to)->setInc('score',$num);
                User::get($from)->setDec('score',$num + $poundage);
                $model->saveAll($data);
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                return false;
            }
        }
        else 
        {
            return false;
        }
        
    }
    
    
    /**
     * 鱼塘收获
     */
    public static function harvestSelf($uid)
    {
        //现有鱼数
        $uid_score = User::where(['id'=>$uid])->value('score');
        $model  = new self();
        $rate   = $model->getRate();
        
        $num    = round($uid_score * $rate ,2);
        $data   = [
                'user_id'   => $uid,
                'score'     => $num,
                'before'    => $uid_score,
                'after'     => $uid_score + $num,
                'memo'      => $model->tradeType['harvest_self'],
            ];
        Db::startTrans();
        try {
            Db::commit();
            User::get($uid)->setInc('score',$num);
            $model->save($data);
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            
            return $e->getMessage();
            return false;
        }
    }
    
    
    
    
    
}
