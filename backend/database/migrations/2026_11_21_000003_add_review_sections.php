<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('review_assignments',fn(Blueprint $t)=>$t->json('completed_sections')->nullable());}
 public function down():void {Schema::table('review_assignments',fn(Blueprint $t)=>$t->dropColumn('completed_sections'));}
};
