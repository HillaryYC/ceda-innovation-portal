<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::create('operation_receipts',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained();$t->uuid('request_key');$t->string('fingerprint',64);$t->text('response');$t->unsignedSmallInteger('status');$t->timestamp('created_at');$t->unique(['user_id','request_key']);});}public function down():void{Schema::dropIfExists('operation_receipts');}};
