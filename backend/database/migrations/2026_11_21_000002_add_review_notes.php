<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('review_assignments',function(Blueprint $t){$t->json('review_notes')->nullable();$t->string('recommendation')->nullable();});}
 public function down():void {Schema::table('review_assignments',fn(Blueprint $t)=>$t->dropColumn(['review_notes','recommendation']));}
};
