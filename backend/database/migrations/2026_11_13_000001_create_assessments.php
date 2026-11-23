<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
 Schema::table('users',fn(Blueprint $t)=>$t->string('role')->default('evaluator'));
 Schema::create('ideas',function(Blueprint $t){$t->id();foreach(['title','team','initial','category','location','color','ink'] as $f)$t->string($f);$t->date('submitted_at');foreach(['summary','problem','solution'] as $f)$t->text($f);$t->string('status')->default('pending')->index();$t->unsignedTinyInteger('complete')->default(100);$t->json('checks');foreach(['notes','information_request','reason'] as $f)$t->text($f)->nullable();$t->timestamps();});
 Schema::create('evaluators',function(Blueprint $t){$t->id();$t->string('name');$t->string('specialty');$t->string('initials');$t->timestamps();});
 Schema::create('evaluator_idea',function(Blueprint $t){$t->foreignId('idea_id')->constrained()->cascadeOnDelete();$t->foreignId('evaluator_id')->constrained()->cascadeOnDelete();$t->unique(['idea_id','evaluator_id']);});
 Schema::create('assessment_events',function(Blueprint $t){$t->id();$t->foreignId('idea_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained();$t->string('action');$t->text('detail')->nullable();$t->timestamps();});
 }
 public function down():void{foreach(['assessment_events','evaluator_idea','evaluators','ideas'] as $t)Schema::dropIfExists($t);Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('role'));}
};