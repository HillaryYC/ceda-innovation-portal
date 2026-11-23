<?php
namespace Tests\Feature;

use App\Models\{Idea,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AdjudicationReadinessTest extends \Illuminate\Foundation\Testing\TestCase {
 use RefreshDatabase;
 public function createApplication() {$app=require __DIR__.'/../../bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();return $app;}
 public function test_adjudicator_cannot_open_assignment_until_all_evaluators_submit():void {
  $programme=DB::table('programmes')->value('id');
  $adjudicator=User::create(['name'=>'Adjudicator','email'=>'adjudicator-ready@example.test','password'=>'Password-strong-123']);$adjudicator->member_type='staff';$adjudicator->account_status='active';$adjudicator->mfa_confirmed_at=now();$adjudicator->save();
  DB::table('programme_role_user')->insert(['user_id'=>$adjudicator->id,'programme_id'=>$programme,'role_id'=>DB::table('roles')->where('name','Adjudicator')->value('id')]);
  $idea=Idea::create(['title'=>'Proposal','team'=>'Applicant','initial'=>'','color'=>'#fff','ink'=>'#000','category'=>'Agriculture','location'=>'Gaborone','programme_id'=>$programme,'summary'=>'Grow food','problem'=>'Supply','solution'=>'Farm','status'=>'assigned','complete'=>100,'checks'=>[]]);
  $evaluation=DB::table('review_templates')->insertGetId(['programme_id'=>$programme,'name'=>'Evaluation','kind'=>'evaluation','version'=>1,'criteria'=>'[]','active'=>true,'created_at'=>now(),'updated_at'=>now()]);
  $adjudication=DB::table('review_templates')->insertGetId(['programme_id'=>$programme,'name'=>'Adjudication','kind'=>'adjudication','version'=>1,'criteria'=>'[]','active'=>true,'created_at'=>now(),'updated_at'=>now()]);
  foreach([['evaluation','submitted',$evaluation],['evaluation','pending',$evaluation],['adjudication','pending',$adjudication]] as [$kind,$status,$template])DB::table('review_assignments')->insert(['idea_id'=>$idea->id,'user_id'=>$adjudicator->id,'template_id'=>$template,'kind'=>$kind,'status'=>$status,'active'=>true,'is_lead'=>false,'assigned_by'=>$adjudicator->id,'created_at'=>now(),'updated_at'=>now()]);
  $this->actingAs($adjudicator)->withSession(['mfa_user_id'=>$adjudicator->id]);
  $this->getJson('/api/v1/my-adjudications')->assertOk()->assertJsonCount(0,'data');
  DB::table('review_assignments')->where('idea_id',$idea->id)->where('kind','evaluation')->update(['status'=>'submitted']);
  $this->getJson('/api/v1/my-adjudications')->assertOk()->assertJsonCount(1,'data');
 }
}
