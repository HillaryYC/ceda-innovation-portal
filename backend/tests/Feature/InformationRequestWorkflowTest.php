<?php
namespace Tests\Feature;
use App\Models\{Idea,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class InformationRequestWorkflowTest extends \Illuminate\Foundation\Testing\TestCase {
 use RefreshDatabase;
 public function createApplication(){$app=require __DIR__.'/../../bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();return $app;}
 public function test_reviewer_request_reaches_owner_and_response_returns_idea_to_review():void{
  $programme=DB::table('programmes')->value('id');
  $reviewer=User::create(['name'=>'Evaluator','email'=>'request-evaluator@example.test','password'=>'Password-strong-123']);$reviewer->member_type='staff';$reviewer->account_status='active';$reviewer->onboarding_completed_at=now();$reviewer->mfa_confirmed_at=now();$reviewer->save();
  DB::table('programme_role_user')->insert(['user_id'=>$reviewer->id,'programme_id'=>$programme,'role_id'=>DB::table('roles')->where('name','Evaluator')->value('id')]);
  $owner=User::create(['name'=>'Applicant','email'=>'request-owner@example.test','password'=>'Password-strong-123']);$owner->member_type='applicant';$owner->account_status='active';$owner->save();
  $stranger=User::create(['name'=>'Other','email'=>'request-other@example.test','password'=>'Password-strong-123']);$stranger->member_type='applicant';$stranger->account_status='active';$stranger->save();
  $idea=Idea::create(['title'=>'Test idea','team'=>'Applicant','initial'=>'','color'=>'#fff','ink'=>'#000','category'=>'Agriculture','location'=>'Gaborone','programme_id'=>$programme,'applicant_id'=>$owner->id,'summary'=>'Summary','problem'=>'Problem','solution'=>'Solution','status'=>'assigned','complete'=>100,'checks'=>[]]);
  $template=DB::table('review_templates')->insertGetId(['programme_id'=>$programme,'name'=>'Test','kind'=>'evaluation','version'=>1,'criteria'=>json_encode([['name'=>'Impact','max'=>10]]),'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
  $assignment=DB::table('review_assignments')->insertGetId(['idea_id'=>$idea->id,'user_id'=>$reviewer->id,'template_id'=>$template,'kind'=>'evaluation','status'=>'pending','active'=>true,'is_lead'=>true,'assigned_by'=>$reviewer->id,'created_at'=>now(),'updated_at'=>now()]);
  $this->actingAs($reviewer)->withSession(['mfa_user_id'=>$reviewer->id])->postJson('/api/v1/review-assignments/'.$assignment.'/information-requests',['question'=>'Please explain the maintenance budget for the first six months.'])->assertCreated()->assertJsonPath('status','open');
  $this->assertDatabaseHas('ideas',['id'=>$idea->id,'status'=>'information']);
  $this->actingAs($stranger)->postJson('/api/v1/applicant/applications/'.$idea->id.'/information-response',['response'=>'An unrelated answer.'])->assertNotFound();
  $this->getJson('/api/v1/review-assignments/'.$assignment.'/information-requests')->assertForbidden();
  $this->actingAs($owner)->getJson('/api/v1/applicant/applications')->assertOk()->assertJsonPath('data.0.information_requests.0.status','open');
  $this->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('data.0.kind','information_requested');
  $pdf='%PDF-1.4'."\n".'1 0 obj << /Type /Catalog >> endobj'."\n".'%%EOF';
  $this->postJson('/api/v1/applicant/applications/'.$idea->id.'/information-response',['response'=>'We budget P1,500 monthly for maintenance and assign the cooperative treasurer to approve repairs.','file'=>['name'=>'sales.pdf','content'=>base64_encode($pdf)]])->assertOk()->assertJsonPath('status','responded')->assertJsonPath('attachment.name','sales.pdf');
  $this->assertDatabaseHas('ideas',['id'=>$idea->id,'status'=>'assigned']);
  $this->postJson('/api/v1/applicant/applications/'.$idea->id.'/information-response',['response'=>'A second answer should fail.'])->assertStatus(409);
  $this->actingAs($reviewer)->getJson('/api/v1/my-evaluations')->assertOk()->assertJsonPath('data.0.information_requests.0.status','responded')->assertJsonPath('data.0.information_requests.0.attachment_name','sales.pdf');
  $this->getJson('/api/v1/review-assignments/'.$assignment.'/information-requests')->assertOk()->assertJsonPath('data.0.attachment_name','sales.pdf');
  $this->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('data.0.kind','information_response');
  $this->postJson('/api/v1/review-assignments/'.$assignment.'/information-requests',['question'=>'Please provide evidence of recent sales.'])->assertCreated();
  $path=tempnam(sys_get_temp_dir(),'ceda-test-xlsx-');$zip=new \ZipArchive();$zip->open($path,\ZipArchive::OVERWRITE);$zip->addFromString('[Content_Types].xml','<Types/>');$zip->addFromString('xl/workbook.xml','<workbook/>');$zip->addFromString('xl/worksheets/sheet1.xml','<worksheet><sheetData><row><c t="inlineStr"><is><t>Sales</t></is></c></row></sheetData></worksheet>');$zip->close();$xlsx=file_get_contents($path);unlink($path);
  $this->actingAs($owner)->postJson('/api/v1/applicant/applications/'.$idea->id.'/information-response',['response'=>'The attached sheet shows our recent sales.','file'=>['name'=>'sales.xlsx','content'=>base64_encode($xlsx)]])->assertOk()->assertJsonPath('attachment.name','sales.xlsx');
  $attachmentId=DB::table('information_requests')->where('idea_id',$idea->id)->orderByDesc('id')->value('attachment_id');
  $this->get('/api/v1/attachments/'.$attachmentId.'/preview')->assertOk()->assertSee('Sales');
 }
}
