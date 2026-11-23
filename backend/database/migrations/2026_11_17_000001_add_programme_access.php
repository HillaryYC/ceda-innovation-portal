<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::create('programmes',function(Blueprint $t){$t->id();$t->string('name');$t->string('code')->unique();$t->timestamps();});
  Schema::create('roles',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->timestamps();});
  Schema::create('permissions',function(Blueprint $t){$t->id();$t->string('name')->unique();});
  Schema::create('permission_role',function(Blueprint $t){$t->foreignId('role_id')->constrained()->cascadeOnDelete();$t->foreignId('permission_id')->constrained()->cascadeOnDelete();$t->unique(['role_id','permission_id']);});
  Schema::create('programme_role_user',function(Blueprint $t){$t->foreignId('programme_id')->constrained()->cascadeOnDelete();$t->foreignId('role_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->unique(['programme_id','role_id','user_id']);});
  $id=DB::table('programmes')->insertGetId(['name'=>'CEDA Innovation Programme','code'=>'CEDA-INNOVATION','created_at'=>now(),'updated_at'=>now()]);
  Schema::table('ideas',fn(Blueprint $t)=>$t->foreignId('programme_id')->nullable()->constrained()->restrictOnDelete());
  DB::table('ideas')->update(['programme_id'=>$id]);
 }
 public function down():void {Schema::table('ideas',function(Blueprint $t){$t->dropConstrainedForeignId('programme_id');});foreach(['programme_role_user','permission_role','permissions','roles','programmes'] as $table)Schema::dropIfExists($table);}
};
