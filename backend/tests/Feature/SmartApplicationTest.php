<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class SmartApplicationTest extends \Illuminate\Foundation\Testing\TestCase {
 use RefreshDatabase;
 public function createApplication(){$app=require __DIR__.'/../../bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();return $app;}

 public function test_industry_and_stage_questions_are_versioned_and_validated():void {
  $admin=User::create(['name'=>'Administrator','email'=>'smart-admin@example.test','password'=>'Test-password-123']);$admin->is_master=true;$admin->save();
  $this->artisan('ceda:publish-smart-form')->assertExitCode(0);
  $programme=(int)DB::table('programmes')->value('id');$schema=\App\Services\FormCatalog::current($programme);
  $this->assertSame('smart-v1',$schema['layout']);$this->assertArrayHasKey('Poultry',$schema['categories']);$this->assertArrayHasKey('Furniture and metalwork',$schema['categories']);
  $this->assertNotSame(array_column($schema['categories']['Poultry']['questions'],'key'),array_column($schema['categories']['Furniture and metalwork']['questions'],'key'));
  $this->artisan('ceda:publish-smart-form')->assertExitCode(0);$this->assertDatabaseCount('form_definitions',1);
  $applicant=User::create(['name'=>'Applicant','email'=>'smart-applicant@example.test','password'=>'Test-password-123']);$applicant->member_type='applicant';$applicant->save();$this->actingAs($applicant->fresh());
  $value=static function(array $q):string|array{if($q['type']==='multiselect')return [$q['options'][0]];if($q['type']==='select')return $q['options'][0];if($q['type']==='number')return '1';return 'A clear answer for this business.';};
  $core=[];foreach($schema['core'] as $questions)foreach($questions as $q)if($q['required'])$core[$q['key']]=$value($q);
  $core['applying_as']='Individual';unset($core['team_size']);
  $core['amount_requested']='1000';$core['use_equipment']='1000';
  $industry=[];foreach($schema['categories']['Poultry']['questions'] as $q)if($q['required']&&(empty($q['stages'])||in_array('Starting or testing',$q['stages'],true)))$industry[$q['key']]=$value($q);
  $body=['programme_id'=>$programme,'title'=>'Poultry business','category'=>'Poultry','location'=>'Maun','summary'=>'Raise chickens','problem'=>'Demand for local chicken','solution'=>'Supply local chicken','form_version'=>$schema['version'],'form_answers'=>['customers'=>'Local households','business_stage'=>'Starting or testing','core_answers'=>$core,'category_answers'=>['Poultry'=>$industry]],'draft_step'=>4,'submit'=>true,'declaration'=>true];
  $bad=$body;$bad['form_answers']['core_answers']['use_equipment']='900';$this->postJson('/api/v1/applicant/applications',$bad)->assertUnprocessable()->assertJsonValidationErrors('funding');
  $bad=$body;unset($bad['form_answers']['category_answers']['Poultry']['bird_type']);$this->postJson('/api/v1/applicant/applications',$bad)->assertUnprocessable();
  $bad=$body;$bad['form_answers']['core_answers']['applying_as']='Group';$this->postJson('/api/v1/applicant/applications',$bad)->assertUnprocessable()->assertJsonValidationErrors('form_answers.core_answers.team_size');
  $this->postJson('/api/v1/applicant/applications',$body)->assertOk()->assertJsonPath('data.category','Poultry')->assertJsonPath('data.form_schema.layout','smart-v1')->assertJsonPath('data.form_answers.core_answers.amount_requested','1000')->assertJsonPath('data.form_answers.category_answers.Poultry.bird_type','Broilers')->assertJsonMissingPath('data.form_answers.core_answers.team_size');
  $draft=$body;$draft['submit']=false;$draft['declaration']=false;
  $draftId=$this->postJson('/api/v1/applicant/applications',$draft)->assertOk()->json('data.id');
  $other=User::create(['name'=>'Other Applicant','email'=>'other-applicant@example.test','password'=>'Test-password-123']);$other->member_type='applicant';$other->save();
  $this->actingAs($other->fresh())->deleteJson('/api/v1/applicant/applications/'.$draftId)->assertNotFound();
  $this->actingAs($applicant->fresh())->deleteJson('/api/v1/applicant/applications/'.$draftId)->assertNoContent();
  $this->assertDatabaseMissing('ideas',['id'=>$draftId]);
 }

 public function test_every_industry_has_only_its_own_questions_and_new_schema_publishes_a_revision():void {
  $schema=config('smart_application_form');
  $this->assertSame(['Starting or testing','Operating business'],$schema['stages']);
  $this->assertCount(17,$schema['categories']);
  foreach($schema['categories'] as $category=>$definition){
   $groups=array_values(array_unique(array_column($definition['questions'],'group')));
   $this->assertEqualsCanonicalizing(['Your progress',$definition['family'],$category],$groups,$category);
   $keys=array_column($definition['questions'],'key');
   $this->assertCount(count(array_unique($keys)),$keys,$category);
  }
  $admin=User::create(['name'=>'Administrator','email'=>'smart-version-admin@example.test','password'=>'Test-password-123']);$admin->is_master=true;$admin->save();
  $this->artisan('ceda:publish-smart-form')->assertExitCode(0);
  $programme=(int)DB::table('programmes')->value('id');
  $first=DB::table('form_definitions')->where('programme_id',$programme)->first();
  $this->artisan('ceda:publish-smart-form')->assertExitCode(0);
  $this->assertDatabaseCount('form_definitions',1);
  $changed=json_decode($first->definition,true);$changed['stages'][0]='Older wording';
  DB::table('form_definitions')->where('id',$first->id)->update(['definition'=>json_encode($changed)]);
  $this->artisan('ceda:publish-smart-form')->assertExitCode(0);
  $this->assertDatabaseCount('form_definitions',2);
  $this->assertSame('programme-'.$programme.'-v2',\App\Services\FormCatalog::current($programme)['version']);
 }
}
