<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::table('ideas',fn(Blueprint $t)=>$t->string('allocation_wait_reason')->nullable());Schema::table('review_assignments',fn(Blueprint $t)=>$t->foreignId('assigned_by')->nullable()->change());}public function down():void{Schema::table('ideas',fn(Blueprint $t)=>$t->dropColumn('allocation_wait_reason'));}};
