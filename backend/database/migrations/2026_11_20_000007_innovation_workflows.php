<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::create('form_definitions',function(Blueprint $t){$t->id();$t->foreignId('programme_id')->constrained();$t->unsignedInteger('revision');$t->json('definition');$t->foreignId('published_by')->constrained('users');$t->timestamps();$t->unique(['programme_id','revision']);});
  Schema::create('reviewer_preferences',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained();$t->foreignId('programme_id')->constrained();$t->string('category');$t->unique(['user_id','programme_id','category']);});
  Schema::table('users',function(Blueprint $t){$t->timestamp('onboarding_completed_at')->nullable();$t->string('phone',40)->nullable()->unique();$t->timestamp('phone_verified_at')->nullable();});
  Schema::table('review_assignments',function(Blueprint $t){$t->string('allocation_source')->default('manual');});
  Schema::table('staff_invitations',function(Blueprint $t){$t->string('delivery_status')->default('manual');$t->string('delivery_error')->nullable();});
 }
 public function down():void{Schema::dropIfExists('reviewer_preferences');Schema::dropIfExists('form_definitions');Schema::table('users',fn(Blueprint $t)=>$t->dropColumn(['onboarding_completed_at','phone','phone_verified_at']));Schema::table('review_assignments',fn(Blueprint $t)=>$t->dropColumn('allocation_source'));Schema::table('staff_invitations',fn(Blueprint $t)=>$t->dropColumn(['delivery_status','delivery_error']));}
};
