<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::table('users',function(Blueprint $t){$t->string('account_status')->default('active');$t->boolean('is_master')->default(false);$t->string('member_type')->default('staff');$t->string('expertise')->nullable();$t->string('availability')->default('available');$t->timestamp('access_expires_at')->nullable();});
  DB::statement('CREATE UNIQUE INDEX one_active_master ON users (is_master) WHERE is_master = true');
  Schema::create('access_overrides',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained();$t->foreignId('programme_id')->constrained();$t->string('permission');$t->boolean('allowed');$t->unique(['user_id','programme_id','permission']);});
  Schema::create('staff_invitations',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained();$t->string('token_hash',64)->unique();$t->timestamp('expires_at');$t->timestamp('accepted_at')->nullable();$t->timestamp('revoked_at')->nullable();$t->timestamp('confidentiality_accepted_at')->nullable();$t->timestamps();});
  Schema::create('audit_events',function(Blueprint $t){$t->id();$t->foreignId('actor_id')->nullable()->constrained('users');$t->foreignId('programme_id')->nullable()->constrained();$t->string('action');$t->string('subject_type');$t->unsignedBigInteger('subject_id');$t->json('detail');$t->timestamp('created_at')->useCurrent();});
  Schema::create('review_templates',function(Blueprint $t){$t->id();$t->foreignId('programme_id')->constrained();$t->string('name');$t->string('kind');$t->unsignedInteger('version')->default(1);$t->json('criteria');$t->boolean('active')->default(true);$t->timestamps();});
  Schema::create('review_assignments',function(Blueprint $t){$t->id();$t->foreignId('idea_id')->constrained();$t->foreignId('user_id')->constrained();$t->foreignId('template_id')->constrained('review_templates');$t->string('kind');$t->string('status')->default('pending');$t->boolean('active')->default(true);$t->boolean('is_lead')->default(false);$t->date('due_date')->nullable();$t->foreignId('assigned_by')->constrained('users');$t->text('reason')->nullable();$t->timestamp('started_at')->nullable();$t->timestamp('submitted_at')->nullable();$t->timestamps();$t->index(['idea_id','active']);});
  $permissions=['applications.view','people.manage','reviews.manage','reviews.evaluate','reviews.adjudicate','templates.manage','reports.view'];
  foreach($permissions as $p) DB::table('permissions')->insertOrIgnore(['name'=>$p]);
  foreach(['Programme Administrator'=>['applications.view','people.manage','reviews.manage','templates.manage','reports.view'],'Evaluator'=>['reviews.evaluate'],'Adjudicator'=>['reviews.adjudicate'],'Viewer'=>['applications.view','reports.view']] as $name=>$grants){DB::table('roles')->insertOrIgnore(['name'=>$name,'created_at'=>now(),'updated_at'=>now()]);$role=DB::table('roles')->where('name',$name)->value('id');foreach($grants as $grant)DB::table('permission_role')->insertOrIgnore(['role_id'=>$role,'permission_id'=>DB::table('permissions')->where('name',$grant)->value('id')]);}
 }
 public function down():void {foreach(['review_assignments','review_templates','audit_events','staff_invitations','access_overrides'] as $table)Schema::dropIfExists($table);DB::statement('DROP INDEX one_active_master');Schema::table('users',fn(Blueprint $t)=>$t->dropColumn(['account_status','is_master','member_type','expertise','availability','access_expires_at']));}
};
