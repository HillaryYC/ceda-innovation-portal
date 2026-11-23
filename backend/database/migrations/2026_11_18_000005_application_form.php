<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::table('ideas',function(Blueprint $t){
   $t->string('form_version')->nullable();$t->json('form_answers')->nullable();$t->json('form_schema')->nullable();
   $t->unsignedTinyInteger('draft_step')->default(0);$t->timestamp('declaration_at')->nullable();$t->date('submitted_at')->nullable()->change();
  });
  Schema::create('application_attachments',function(Blueprint $t){
   $t->id();$t->foreignId('idea_id')->constrained()->cascadeOnDelete();$t->string('name',200);$t->string('mime',100);$t->unsignedInteger('size');$t->text('content');$t->timestamps();
  });
 }
 public function down():void{
  Schema::dropIfExists('application_attachments');
  Schema::table('ideas',fn(Blueprint $t)=>$t->dropColumn(['form_version','form_answers','form_schema','draft_step','declaration_at']));
 }
};
