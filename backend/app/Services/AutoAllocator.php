<?php
namespace App\Services;
use App\Models\{Idea,User};
use Illuminate\Support\Facades\DB;

class AutoAllocator {
 public function assignReady(int $programme):void{foreach(Idea::where('programme_id',$programme)->where('status','ready')->where('complete',100)->limit(250)->pluck('id') as $id)$this->assign($id);}
 public function assign(int $id):?int{return DB::transaction(function()use($id){
  $idea=Idea::findOrFail($id);
  DB::table('programmes')->where('id',$idea->programme_id)->lockForUpdate()->first();
  $idea=Idea::lockForUpdate()->findOrFail($id);
  if(!in_array($idea->status,['ready','pending'])||$idea->complete!==100||DB::table('review_assignments')->where('idea_id',$id)->where('active',true)->exists())return null;
  $summary=ReviewCategoryMap::for($idea->programme_id,$idea->category);
  if(!$summary)return $this->wait($idea,'No evaluator category mapping exists for '.$idea->category.'.');
  $templates=DB::table('review_templates')->where('programme_id',$idea->programme_id)->where('kind','evaluation')->where('active',true)->get();
  if($templates->count()!==1)return $this->wait($idea,'Choose exactly one active evaluation template for this programme.');
  $access=new ProgrammeAccess;
  $pool=User::where('account_status','active')->where('availability','available')->whereNotNull('onboarding_completed_at')->orderBy('id')->lockForUpdate()->get()->filter(fn($u)=>Administration::active($u)&&(!Administration::requiresMfa($u)||$u->mfa_confirmed_at)&&$access->allows($u,$idea->programme_id,'reviews.evaluate')&&DB::table('reviewer_preferences')->where('user_id',$u->id)->where('programme_id',$idea->programme_id)->where('category',$summary)->exists());
  $ranked=$pool->map(function($u){$work=DB::table('review_assignments')->where('user_id',$u->id)->where('active',true)->whereIn('status',['pending','in_progress'])->count();$last=DB::table('review_assignments')->where('user_id',$u->id)->max('id')??0;return compact('u','work','last');})->filter(fn($x)=>$x['work']<config('portal.allocation_max_workload',20));
  if($ranked->isEmpty())return $this->wait($idea,'No available evaluator selected '.$summary.' with capacity.');
  $winner=$ranked->sortBy(fn($x)=>[$x['work'],$x['last'],$x['u']->id])->first();
  DB::table('review_assignments')->insert(['idea_id'=>$id,'user_id'=>$winner['u']->id,'template_id'=>$templates->first()->id,'kind'=>'evaluation','active'=>true,'is_lead'=>true,'status'=>'pending','assigned_by'=>null,'allocation_source'=>'preference','reason'=>'Automatic '.$summary.' allocation; open workload '.$winner['work'],'created_at'=>now(),'updated_at'=>now()]);
  $idea->status='assigned';$idea->allocation_wait_reason=null;$idea->save();
  PortalNotifications::assigned($winner['u']->id,$idea);
  Administration::audit(null,'review.auto_allocated','idea',$id,['evaluator_id'=>$winner['u']->id,'summary_category'=>$summary,'workload'=>$winner['work']],$idea->programme_id);
  return $winner['u']->id;
 });}
 private function wait(Idea $idea,string $reason):?int{if($idea->allocation_wait_reason!==$reason){$idea->allocation_wait_reason=$reason;$idea->save();PortalNotifications::admins($idea);Administration::audit(null,'review.awaiting_allocation','idea',$idea->id,['reason'=>$reason],$idea->programme_id);}return null;}
}
