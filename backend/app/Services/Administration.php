<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class Administration {
 public static function active(User $u):bool{return $u->account_status==='active'&&(!$u->access_expires_at||now()->lt($u->access_expires_at));}
 public static function master(User $u):bool{return self::active($u)&&(bool)$u->is_master;}
 public static function requiresMfa(User $u):bool{if(config('portal.preview_bypass_mfa')&&str_ends_with($u->email,'@preview.test'))return false;if($u->is_master&&!config('portal.master_mfa_enabled'))return false;if(!config('portal.reviewer_mfa_enabled')&&self::reviewerOnly($u))return false;return (bool)$u->is_master||in_array($u->member_type,['staff','external'],true);}
 public static function reviewerOnly(User $u):bool{$roles=DB::table('programme_role_user as p')->join('roles','roles.id','=','p.role_id')->where('p.user_id',$u->id)->distinct()->pluck('roles.name')->all();return count($roles)>0&&count(array_diff($roles,['Evaluator','Adjudicator']))===0;}
 public static function freshVerification(User $u,$session):bool{return !self::requiresMfa($u)||$session->get('mfa_verified_at',0)>time()-900;}
 public static function audit(?User $actor,string $action,string $type,int $id,array $detail=[],?int $programme=null):void{DB::table('audit_events')->insert(['actor_id'=>$actor?->id,'programme_id'=>$programme,'action'=>$action,'subject_type'=>$type,'subject_id'=>$id,'detail'=>json_encode($detail),'created_at'=>now()]);}
 public static function lockMaster():void{DB::table('users')->where('is_master',true)->lockForUpdate()->first();}
 public static function person(User $u):array{return ['id'=>$u->id,'name'=>$u->name,'email'=>$u->email,'status'=>$u->account_status,'is_master'=>(bool)$u->is_master,'type'=>$u->member_type,'expertise'=>$u->expertise,'availability'=>$u->availability,'expires_at'=>$u->access_expires_at,'last_invitation'=>DB::table('staff_invitations')->where('user_id',$u->id)->latest('id')->first(['delivery_status','delivery_error','expires_at']),'roles'=>DB::table('programme_role_user as p')->join('roles','roles.id','=','p.role_id')->where('p.user_id',$u->id)->get(['p.programme_id','roles.id as role_id','roles.name']),'overrides'=>DB::table('access_overrides')->where('user_id',$u->id)->get(['programme_id','permission','allowed'])];}
}
