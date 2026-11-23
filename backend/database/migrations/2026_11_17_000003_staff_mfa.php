<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::table('users',function(Blueprint $t){$t->text('mfa_secret')->nullable();$t->timestamp('mfa_confirmed_at')->nullable();$t->bigInteger('mfa_last_step')->nullable();});}public function down():void{Schema::table('users',fn(Blueprint $t)=>$t->dropColumn(['mfa_secret','mfa_confirmed_at','mfa_last_step']));}};
