<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('evaluator_teams',function(Blueprint $t){$t->id();$t->foreignId('programme_id')->constrained()->cascadeOnDelete();$t->string('name',120);$t->timestamps();$t->unique(['programme_id','name']);});
  Schema::create('evaluator_team_members',function(Blueprint $t){$t->foreignId('team_id')->constrained('evaluator_teams')->cascadeOnDelete();$t->foreignId('user_id')->constrained('users')->cascadeOnDelete();$t->primary(['team_id','user_id']);});
 }
 public function down():void {Schema::dropIfExists('evaluator_team_members');Schema::dropIfExists('evaluator_teams');}
};
