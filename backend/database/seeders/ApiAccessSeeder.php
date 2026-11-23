<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
class ApiAccessSeeder extends Seeder {
 public function run():void {
  if(!app()->environment(['local','testing']))throw new \RuntimeException('Demo access is restricted to development.');
  $user=User::where('email','admin@ceda.test')->firstOrFail();
  DB::table('roles')->insertOrIgnore(['name'=>'Programme Administrator','created_at'=>now(),'updated_at'=>now()]);
  DB::table('permissions')->insertOrIgnore(['name'=>'applications.view']);
  $role=DB::table('roles')->where('name','Programme Administrator')->value('id');$permission=DB::table('permissions')->where('name','applications.view')->value('id');
  DB::table('permission_role')->insertOrIgnore(['role_id'=>$role,'permission_id'=>$permission]);
  DB::table('programme_role_user')->insertOrIgnore(['programme_id'=>DB::table('programmes')->where('code','CEDA-INNOVATION')->value('id'),'role_id'=>$role,'user_id'=>$user->id]);
 }
}
