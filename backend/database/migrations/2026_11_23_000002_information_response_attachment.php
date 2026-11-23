<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void{Schema::table('information_requests',function(Blueprint $table){$table->foreignId('attachment_id')->nullable()->constrained('application_attachments')->nullOnDelete();});}
 public function down():void{Schema::table('information_requests',function(Blueprint $table){$table->dropConstrainedForeignId('attachment_id');});}
};
