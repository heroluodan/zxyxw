<?php
namespace app\api\model;
use think\Model;
use app\common\model\User;
/**
 * 用户其他信息
 * @author Administrator
 *
 */
class UserAttach extends Model
{
    //表名
    protected $name = "user_attach";
    
    /**
     * 缓存秒数
     */
    protected static $expire   = 1;
    
    /**
     * 根据邀请码添加上级
     */
    public static function addParent($uid ,$invitecode="6DK4zy")
    {
        
       if(!cache('user_invite_info'.$invitecode))
       { //获取用户邀请码信息
           $userModel   = new User();
           $user_invite_info = 
           $userModel
           ->alias('a')
           ->where(['a.invitecode'=>$invitecode])
           ->join('user_attach b','a.id=b.uid')
           ->field('a.id,a.invitecode,b.path')
           ->find();
           cache('user_invite_info'.$invitecode,$user_invite_info,self::$expire);
       }
       $user_invite_info = cache('user_invite_info'.$invitecode);
       $data    = [
           'uid'    => $uid,
           'path'   => $user_invite_info['path'].'-'.$user_invite_info['id'],
           'parent_id'  => $user_invite_info['id'],
       ];
       $model = new self();
       if($model->save($data))
           return true;
       return false;
    }
    
    
    /**
     * 获取用户的下级用户ID信息
     */
    public static function getDownUser($uid=25)
    {
        return 
        self::alias('a')
        ->where(['a.uid'=>$uid])
        ->join('user b','a.uid=b.id')
        ->field('b.nickname,b.avatar,b.id')
        ->select();
    }
}

?>