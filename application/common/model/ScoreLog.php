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
                return 1;
            } catch (\Exception $e) {
                Db::rollback();
                return 3;
            }
        }
        else 
        {
            return 2;
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
                'user_id'   => intval($uid),
                'score'     => $num,
                'before'    => $uid_score,
                'after'     => $uid_score + $num,
                'memo'      => $model->tradeType['harvest_self'],
            ];
        Db::startTrans();
        try {
            Db::commit();
            User::get($uid)->setInc('score',$num);
            ScoreLog::insert($data);
            return $num;
        } catch (\Exception $e) {
            Db::rollback(); 
            return $e->getMessage().'--'.$uid;
            return false;
        }
    }
    
    /**
     * 好友处收获
     */
    public static function harvestFriend($uid,$friend=null)
    {
        $today  = strtotime(date('Y-m-d 00:00:00'));
        $model  = new self();
        $harvest    = [
            'user_id'    => $uid,
            'createtime'  => ['egt',$today],
            'memo'  => $model->tradeType['harvest_friend'],
        ];
        //今天从好友出收获数量
        $today_num  = self::where($harvest)->sum('score');
        
        //自己现有鱼数
        $uid_score = User::where(['id'=>$uid])->value('score');
        
        $rate   = $model->getRate();
        $yjnum    = round($uid_score * $rate ,2);//自己能收获的鱼数
        
        if($friend) //收获好友
        {
            $where  = [
                'uid'   => $friend,
                'get_date'      => date('Y-m-d'),
                'is_pull' => 0
            ];
            $info   = Fishing::where($where)->find();
            if(!$info) return ['code'=>0,'msg'=>'不能偷取该玩家'];
            $num    = $info['num']/10; //可以收获的鱼数
           
            //还可以收获的鱼数
            $canNum =  $yjnum - ($today_num + $num);
            if($canNum<0)
                $num    = $canNum + $num;
            $data   = [
                'user_id'   => $uid,
                'from'      => $friend,
                'score'     => $num,
                'before'    => $uid_score,
                'after'     => $uid_score + $num,
                'memo'      => $model->tradeType['harvest_friend'],
            ]; 
            Db::startTrans();
            try {
                Db::commit();
                User::get($uid)->setInc('score',$num);
                $info->is_pull  = 1;
                $info->save();
                $model->save($data);
                return ['code'=>1,'msg'=>'成功偷取'.$num.'条'];
            } catch (\Exception $e) {
                Db::rollback();
                return ['code'=>2,'msg'=>'系统错误'];
                return $e->getMessage();
            }
           
        }
        else //一键收获
        {
            $where  = [
                'get_date'      => date('Y-m-d'),
                'is_pull' => 0
            ];
            //总共能收获的鱼数
            $sumNum   = Fishing::where($where)->sum('num');
            if($sumNum <= 0)
                return ['code'=>3,'msg'=>'没有用户可偷取'];
            $num     = $yjnum - $today_num;
            if($sumNum < $num)
                $num    = $sumNum;
            if($num <= 0)
                return ['code'=>4,'msg'=>'今天已经全部偷取'];
            Db::startTrans();
            try {
                Db::commit();
                User::get($uid)->setInc('score',$num);
                $fishModel  = new Fishing();
                $fishModel->where($where)->data(['is_pull'=>1])->update();
                $data   = [
                    'user_id'   => $uid,
                    'from'      => 0,
                    'score'     => $num,
                    'before'    => $uid_score,
                    'after'     => $uid_score + $num,
                    'memo'      => $model->tradeType['harvest_friend'],
                ];
                $model->save($data);
                return ['code'=>1,'msg'=>'成功偷取'.$num.'条'];
            } catch (\Exception $e) {
                Db::rollback();
                return ['code'=>2,'msg'=>'系统错误'];
                return $e->getMessage();
            }
        }
        
    }
    
    
    /**
     * 用户转账
     */
    
}
