<?php
namespace Tests\Feature;

use App\Models\{Idea,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class EvaluationSubmissionTest extends \Illuminate\Foundation\Testing\TestCase {
 use RefreshDatabase;
 public function createApplication() {$app=require __DIR__.'/../../bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();return $app;}
 public function test_admin_defined_point_ranges_submit_without_annotation_notes():void {
  $programme=DB::table('programmes')->value('id');
  $reviewer=User::create(['name'=>'Preview Evaluator','email'=>'reviewer-range@example.test','password'=>'Password-strong-123']);$reviewer->member_type='staff';$reviewer->account_status='active';$reviewer->onboarding_completed_at=now();$reviewer->mfa_confirmed_at=now();$reviewer->save();
  DB::table('programme_role_user')->insert(['user_id'=>$reviewer->id,'programme_id'=>$programme,'role_id'=>DB::table('roles')->where('name','Evaluator')->value('id')]);
  $idea=Idea::create(['title'=>'Farm proposal','team'=>'Applicant','initial'=>'','color'=>'#fff','ink'=>'#000','category'=>'Agriculture','location'=>'Gaborone','programme_id'=>$programme,'summary'=>'Grow food','problem'=>'Limited supply','solution'=>'New farm','status'=>'assigned','complete'=>100,'checks'=>[]]);
  $template=DB::table('review_templates')->insertGetId(['programme_id'=>$programme,'name'=>'Wide range','kind'=>'evaluation','version'=>1,'criteria'=>json_encode([['name'=>'Feasibility','max'=>100],['name'=>'Impact','max'=>50]]),'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
  $assignment=DB::table('review_assignments')->insertGetId(['idea_id'=>$idea->id,'user_id'=>$reviewer->id,'template_id'=>$template,'kind'=>'evaluation','status'=>'pending','active'=>true,'is_lead'=>true,'assigned_by'=>$reviewer->id,'created_at'=>now(),'updated_at'=>now()]);
  $this->actingAs($reviewer)->withSession(['mfa_user_id'=>$reviewer->id]);
  $body=['marks'=>[['score'=>80,'comment'=>''],['score'=>35,'comment'=>'']],'review_notes'=>[],'completed_sections'=>[0,1,2,3,4],'overall_comment'=>'Promising with manageable risks.','recommendation'=>'conditions','submit'=>true];
  $this->putJson('/api/v1/my-evaluations/'.$assignment,$body)->assertOk()->assertJsonPath('status','submitted');
  $this->assertDatabaseHas('review_assignments',['id'=>$assignment,'status'=>'submitted','recommendation'=>'conditions']);
  $this->putJson('/api/v1/my-evaluations/'.$assignment,$body)->assertStatus(409);
 }
}
