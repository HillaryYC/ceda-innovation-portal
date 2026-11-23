<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {Schema::table('ideas',fn(Blueprint $table)=>$table->timestamp('public_published_at')->nullable());}
 public function down():void {Schema::table('ideas',fn(Blueprint $table)=>$table->dropColumn('public_published_at'));}
};
