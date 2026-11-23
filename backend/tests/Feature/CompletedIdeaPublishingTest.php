<?php
namespace Tests\Feature;

use App\Models\{Idea,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CompletedIdeaPublishingTest extends \Illuminate\Foundation\Testing\TestCase {
 use RefreshDatabase;
 public function createApplication() {$app=require __DIR__.'/../../bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();return $app;}
 public function test_only_completed_funding_decisions_can_be_published():void {
  $programme=DB::table('programmes')->value('id');
  $admin=User::create(['name'=>'Programme Admin','email'=>'publish-admin@example.test','password'=>'Password-strong-123']);$admin->member_type='staff';$admin->account_status='active';$admin->mfa_confirmed_at=now();$admin->save();
  DB::table('programme_role_user')->insert(['user_id'=>$admin->id,'programme_id'=>$programme,'role_id'=>DB::table('roles')->where('name','Programme Administrator')->value('id')]);
  $idea=Idea::create(['title'=>'Farm proposal','team'=>'Applicant','initial'=>'','color'=>'#fff','ink'=>'#000','category'=>'Agriculture','location'=>'Gaborone','programme_id'=>$programme,'summary'=>'Grow local food','problem'=>'Limited supply','solution'=>'New farm','status'=>'assigned','complete'=>100,'checks'=>[]]);
  $template=DB::table('review_templates')->insertGetId(['programme_id'=>$programme,'name'=>'Final review','kind'=>'adjudication','version'=>1,'criteria'=>json_encode([['name'=>'Decision','max'=>100]]),'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
  $evaluationTemplate=DB::table('review_templates')->insertGetId(['programme_id'=>$programme,'name'=>'Evaluation','kind'=>'evaluation','version'=>1,'criteria'=>json_encode([['name'=>'Impact','max'=>100]]),'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
  DB::table('review_assignments')->insert(['idea_id'=>$idea->id,'user_id'=>$admin->id,'template_id'=>$evaluationTemplate,'kind'=>'evaluation','status'=>'submitted','active'=>true,'assigned_by'=>$admin->id,'submitted_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
  $assignment=DB::table('review_assignments')->insertGetId(['idea_id'=>$idea->id,'user_id'=>$admin->id,'template_id'=>$template,'kind'=>'adjudication','status'=>'pending','active'=>true,'assigned_by'=>$admin->id,'created_at'=>now(),'updated_at'=>now()]);
  $this->actingAs($admin)->withSession(['mfa_user_id'=>$admin->id]);
  $this->getJson('/api/v1/applications?stage=complete')->assertOk()->assertJsonCount(0,'data');
  $this->postJson('/api/v1/applications/'.$idea->id.'/publish')->assertStatus(422);
  DB::table('review_assignments')->where('id',$assignment)->update(['status'=>'submitted','recommendation'=>'decline','submitted_at'=>now()]);
  $this->getJson('/api/v1/applications?stage=complete')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.can_publish',false);
  $this->postJson('/api/v1/applications/'.$idea->id.'/publish')->assertStatus(422);
  DB::table('review_assignments')->where('id',$assignment)->update(['recommendation'=>'fund']);
  $this->postJson('/api/v1/applications/'.$idea->id.'/publish')->assertOk();
  $this->getJson('/api/v1/public/awarded-ideas')->assertOk()->assertJsonPath('data.0.title','Farm proposal');
 }
}
