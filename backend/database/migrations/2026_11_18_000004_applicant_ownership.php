<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{Schema::table('ideas',fn(Blueprint $t)=>$t->foreignId('applicant_id')->nullable()->constrained('users'));}
 public function down():void{Schema::table('ideas',fn(Blueprint $t)=>$t->dropConstrainedForeignId('applicant_id'));}
};
