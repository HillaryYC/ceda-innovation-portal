<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{Schema::table('application_attachments',function(Blueprint $t){$t->string('kind')->default('supporting');$t->string('category')->nullable();$t->string('template_version')->nullable();});}
 public function down():void{Schema::table('application_attachments',fn(Blueprint $t)=>$t->dropColumn(['kind','category','template_version']));}
};
