<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::create('information_requests',function(Blueprint $table){
   $table->id();$table->foreignId('idea_id')->constrained('ideas')->cascadeOnDelete();$table->foreignId('assignment_id')->constrained('review_assignments');$table->foreignId('requested_by')->constrained('users');$table->string('source',20);$table->text('question');$table->string('status',20)->default('open');$table->text('response')->nullable();$table->timestamp('responded_at')->nullable();$table->timestamps();$table->index(['idea_id','status']);
  });
 }
 public function down():void{Schema::dropIfExists('information_requests');}
};
