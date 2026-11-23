<?php
namespace Tests\Feature;
use App\Models\{User,Idea};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
class ApplicantFormTest extends \Illuminate\Foundation\Testing\TestCase {
 use RefreshDatabase;
 public function createApplication(){ $app=require __DIR__.'/../../bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();return $app; }
 private function applicant():User{$u=new User(['name'=>'Applicant','email'=>uniqid().'@example.test','password'=>'Test-password-123']);$u->member_type='applicant';$u->email_verified_at=now();$u->save();return $u->fresh();}
 private function payload():array{return ['programme_id'=>DB::table('programmes')->value('id'),'title'=>'Farm idea','category'=>'Agriculture','location'=>'Maun','summary'=>'Summary','problem'=>'Problem','solution'=>'Solution','form_version'=>'online-v2','form_answers'=>['customers'=>'Local buyers','business_stage'=>'Idea / concept','category_answers'=>['Agriculture'=>['activity'=>'Livestock','resources'=>'Land and water','market'=>'Local market','validation_plan'=>'Test with local growers']]],'draft_step'=>2,'submit'=>false];}
 private function proposal(int $id,string $category='Agriculture'):void{$this->postJson('/api/v1/applicant/applications/'.$id.'/attachments',['name'=>'proposal.pdf','content'=>base64_encode("%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF"),'kind'=>'proposal','category'=>$category,'template_version'=>config('application_form.version')])->assertOk();}
 private function legacyDraft(array $d):int{$idea=Idea::create([...collect($d)->only(['title','category','programme_id','location','summary','problem','solution','form_answers'])->all(),'team'=>'Applicant','initial'=>'','color'=>'#fff','ink'=>'#000','applicant_id'=>auth()->id(),'status'=>'draft','complete'=>0,'checks'=>[],'submitted_at'=>null,'form_version'=>config('application_form.version'),'form_schema'=>config('application_form')]);return $idea->id;}
 public function test_word_template_download_and_real_docx_upload():void{
  $this->actingAs($this->applicant());$d=$this->payload();$d['form_answers']=[];$d['problem']='';$d['solution']='';
  $this->get('/api/v1/applicant/templates/agriculture')->assertOk()->assertDownload('CEDA-agriculture-proposal-template.docx');
  $this->get('/api/v1/applicant/templates/invalid')->assertNotFound();
  $d['form_version']=config('application_form.version');$id=$this->legacyDraft($d);
  $body=['name'=>'proposal.docx','content'=>base64_encode(file_get_contents(resource_path('proposal-templates/'.config('application_form.version').'/agriculture.docx'))),'kind'=>'proposal','category'=>'Agriculture','template_version'=>config('application_form.version')];
  $this->postJson('/api/v1/applicant/applications/'.$id.'/attachments',[...$body,'content'=>base64_encode('not a Word file')])->assertUnprocessable();
  $fid=$this->postJson('/api/v1/applicant/applications/'.$id.'/attachments',$body)->assertOk()->assertJsonPath('data.kind','proposal')->json('data.id');
  $this->get('/api/v1/attachments/'.$fid)->assertOk()->assertHeader('Content-Type','application/vnd.openxmlformats-officedocument.wordprocessingml.document');
  $this->putJson('/api/v1/applicant/applications/'.$id,[...$d,'submit'=>true,'declaration'=>true])->assertOk();
 }
 public function test_category_change_requires_matching_proposal_and_replacement_is_atomic():void{
  $this->actingAs($this->applicant());$d=$this->payload();$d['form_version']=config('application_form.version');$id=$this->legacyDraft($d);$this->proposal($id);
  $d['category']='Health';$this->putJson('/api/v1/applicant/applications/'.$id,$d)->assertOk();
  $this->putJson('/api/v1/applicant/applications/'.$id,[...$d,'submit'=>true,'declaration'=>true])->assertUnprocessable();
  $this->assertDatabaseCount('application_attachments',1);$this->proposal($id,'Health');$this->assertDatabaseCount('application_attachments',1);
  $this->putJson('/api/v1/applicant/applications/'.$id,[...$d,'submit'=>true,'declaration'=>true])->assertOk();
 }
 public function test_partial_draft_roundtrips_step_and_version_without_submission_date():void{
  $d=$this->payload();$d['title']='';$d['location']='';$d['form_answers']=[];$d['draft_step']=1;
  $this->actingAs($this->applicant());$id=$this->postJson('/api/v1/applicant/applications',$d)->assertOk()->assertJsonPath('data.submitted_at',null)->json('data.id');
  $this->getJson('/api/v1/applicant/applications')->assertOk()->assertJsonPath('data.0.id',$id)->assertJsonPath('data.0.draft_step',1)->assertJsonPath('data.0.form_schema.version','online-v2');
 }
 public function test_submit_requires_category_answers_and_declaration_and_locks_after_success():void{
  $this->actingAs($this->applicant());$d=$this->payload();$id=$this->postJson('/api/v1/applicant/applications',$d)->assertOk()->json('data.id');
  $bad=$d;$bad['submit']=true;unset($bad['form_answers']['category_answers']);$bad['declaration']=true;
  $this->putJson('/api/v1/applicant/applications/'.$id,$bad)->assertUnprocessable();
  $this->putJson('/api/v1/applicant/applications/'.$id,[...$d,'submit'=>true])->assertUnprocessable();
  $this->putJson('/api/v1/applicant/applications/'.$id,[...$d,'form_version'=>'forged'])->assertUnprocessable();
  $this->proposal($id);
  $this->putJson('/api/v1/applicant/applications/'.$id,[...$d,'submit'=>true,'declaration'=>true])->assertOk()->assertJsonPath('data.status','ready')->assertJsonPath('data.complete',100);
  $idea=Idea::find($id);$this->assertNotNull($idea->declaration_at);$this->assertNotNull($idea->submitted_at);$this->assertSame('Livestock',$idea->form_answers['category_answers']['Agriculture']['activity']);
  $this->putJson('/api/v1/applicant/applications/'.$id,$d)->assertStatus(409);
 }
 public function test_draft_keeps_other_category_answers_but_submission_only_keeps_selected_category():void{
  $this->actingAs($this->applicant());$d=$this->payload();$d['form_answers']['category_answers']['Health']=['need'=>'Care','delivery'=>'Clinic','safety'=>'Professionals'];
  $id=$this->postJson('/api/v1/applicant/applications',$d)->assertOk()->assertJsonPath('data.form_answers.category_answers.Health.need','Care')->json('data.id');
  $this->proposal($id);
  $this->putJson('/api/v1/applicant/applications/'.$id,[...$d,'submit'=>true,'declaration'=>true])->assertOk()->assertJsonMissingPath('data.form_answers.category_answers.Health');
 }
 public function test_files_are_private_validated_durable_and_locked_on_submission():void{
  $owner=$this->applicant();$other=$this->applicant();$d=$this->payload();$this->actingAs($owner);
  $id=$this->postJson('/api/v1/applicant/applications',$d)->assertOk()->json('data.id');$file=['name'=>'support.pdf','content'=>base64_encode("%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF")];
  $fid=$this->postJson('/api/v1/applicant/applications/'.$id.'/attachments',$file)->assertOk()->assertJsonMissingPath('data.content')->json('data.id');
  $this->get('/api/v1/attachments/'.$fid)->assertOk()->assertHeader('Content-Type','application/pdf');
  $this->postJson('/api/v1/applicant/applications/'.$id.'/attachments',['name'=>'fake.pdf','content'=>base64_encode('<script>bad</script>')])->assertUnprocessable();
  $this->actingAs($other)->get('/api/v1/attachments/'.$fid)->assertNotFound();
  $this->deleteJson('/api/v1/applicant/applications/'.$id.'/attachments/'.$fid)->assertNotFound();
  $this->postJson('/api/v1/applicant/applications/'.$id.'/attachments',$file)->assertNotFound();
  $this->actingAs($owner);$this->proposal($id);
  $this->putJson('/api/v1/applicant/applications/'.$id,[...$d,'submit'=>true,'declaration'=>true])->assertOk();
  $this->deleteJson('/api/v1/applicant/applications/'.$id.'/attachments/'.$fid)->assertStatus(409);
  $this->postJson('/api/v1/applicant/applications/'.$id.'/attachments',$file)->assertStatus(409);
  $this->assertDatabaseHas('application_attachments',['id'=>$fid,'idea_id'=>$id]);
 }
}
