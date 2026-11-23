<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
 public function up():void {
  Schema::create('review_summary_categories',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->unsignedInteger('position');});
  Schema::create('review_category_mappings',function(Blueprint $t){$t->id();$t->foreignId('programme_id')->constrained();$t->string('detailed_category');$t->foreignId('summary_category_id')->constrained('review_summary_categories');$t->timestamps();$t->unique(['programme_id','detailed_category']);});
  Schema::create('portal_notifications',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained();$t->foreignId('programme_id')->nullable()->constrained();$t->foreignId('idea_id')->nullable()->constrained('ideas');$t->string('kind',40);$t->string('title',150);$t->string('message',500);$t->timestamp('read_at')->nullable();$t->timestamps();$t->unique(['user_id','idea_id','kind']);$t->index(['user_id','read_at']);});
  foreach(config('review_categories.summary') as $position=>$name)DB::table('review_summary_categories')->insert(['name'=>$name,'position'=>$position]);
  foreach(DB::table('programmes')->pluck('id') as $programme){foreach(config('review_categories.initial_mappings') as $detailed=>$summary){DB::table('review_category_mappings')->insert(['programme_id'=>$programme,'detailed_category'=>$detailed,'summary_category_id'=>DB::table('review_summary_categories')->where('name',$summary)->value('id'),'created_at'=>now(),'updated_at'=>now()]);}}
  foreach(DB::table('reviewer_preferences')->get() as $preference){$mapped=DB::table('review_category_mappings as m')->join('review_summary_categories as s','s.id','=','m.summary_category_id')->where('m.programme_id',$preference->programme_id)->where('m.detailed_category',$preference->category)->value('s.name');if(!$mapped)continue;$exists=DB::table('reviewer_preferences')->where('user_id',$preference->user_id)->where('programme_id',$preference->programme_id)->where('category',$mapped)->where('id','!=',$preference->id)->exists();if($exists)DB::table('reviewer_preferences')->where('id',$preference->id)->delete();else DB::table('reviewer_preferences')->where('id',$preference->id)->update(['category'=>$mapped]);}
  $valid=DB::table('review_summary_categories')->pluck('name')->all();foreach(DB::table('users')->whereNotNull('onboarding_completed_at')->pluck('id') as $user){if(!DB::table('reviewer_preferences')->where('user_id',$user)->whereIn('category',$valid)->exists())DB::table('users')->where('id',$user)->update(['onboarding_completed_at'=>null]);}
 }
 public function down():void {Schema::dropIfExists('portal_notifications');Schema::dropIfExists('review_category_mappings');Schema::dropIfExists('review_summary_categories');}
};
