<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class ProgrammeAccess {
 public function programmes(User $user,string $permission):array {
  if(!Administration::active($user))return [];
  if($user->member_type==='external'&&in_array($permission,['people.manage','templates.manage','reviews.manage'],true))return [];
  $ids=DB::table('programme_role_user as access')->join('permission_role','access.role_id','=','permission_role.role_id')->join('permissions','permissions.id','=','permission_role.permission_id')->where('access.user_id',$user->id)->where('permissions.name',$permission)->distinct()->pluck('access.programme_id')->all();
  $overrides=DB::table('access_overrides')->where('user_id',$user->id)->where('permission',$permission)->get();
  foreach($overrides as $o){if($o->allowed)$ids[]=$o->programme_id;else $ids=array_diff($ids,[$o->programme_id]);}
  return array_values(array_unique(array_map('intval',$ids)));
 }
 public function allows(User $user,int $programme,string $permission):bool {return in_array($programme,$this->programmes($user,$permission));}
}
