<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class ApplicantLoginSecurityTest extends \Illuminate\Foundation\Testing\TestCase {
 use RefreshDatabase;
 public function createApplication(){ $app=require __DIR__.'/../../bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();return $app; }
 public function test_registration_requires_phone_and_strong_password_without_signing_in():void{
  $body=['name'=>'Applicant','email'=>'applicant@example.test','phone'=>'71234567','password'=>'SecureP123!','password_confirmation'=>'SecureP123!','confidentiality'=>true];
  $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
  $this->postJson('/api/v1/applicant/register',[...$body,'phone'=>null])->assertUnprocessable()->assertJsonValidationErrors('phone');
  $this->postJson('/api/v1/applicant/register',[...$body,'phone'=>'7123456'])->assertUnprocessable()->assertJsonValidationErrors('phone');
  $this->postJson('/api/v1/applicant/register',[...$body,'phone'=>'+26871234567'])->assertUnprocessable()->assertJsonValidationErrors('phone');
  $this->postJson('/api/v1/applicant/register',[...$body,'password'=>'alllowercase12','password_confirmation'=>'alllowercase12'])->assertUnprocessable()->assertJsonValidationErrors('password');
  $this->postJson('/api/v1/applicant/register',[...$body,'password'=>'Abc123!','password_confirmation'=>'Abc123!'])->assertUnprocessable()->assertJsonValidationErrors('password');
  $this->postJson('/api/v1/applicant/register',[...$body,'password'=>'Abcdef123456!','password_confirmation'=>'Abcdef123456!'])->assertUnprocessable()->assertJsonValidationErrors('password');
  $this->postJson('/api/v1/applicant/register',$body)->assertCreated();$this->assertGuest();$this->assertDatabaseHas('users',['email'=>$body['email'],'phone'=>'+26771234567']);
  $this->postJson('/api/v1/applicant/register',[...$body,'email'=>'short@example.test','phone'=>'71234568','password'=>'Abcd12!x','password_confirmation'=>'Abcd12!x'])->assertCreated();
 }
 public function test_applicant_needs_code_before_session_is_authenticated():void{
  config()->set('portal.sms_demo',true);
  $u=new User(['name'=>'Applicant','email'=>'applicant@example.test','password'=>'SecurePass123!']);$u->member_type='applicant';$u->phone='+26771234567';$u->save();
  $this->postJson('/api/v1/login',['email'=>$u->email,'password'=>'wrong'])->assertUnprocessable()->assertJsonValidationErrors('email');
  $start=$this->postJson('/api/v1/login',['email'=>$u->email,'password'=>'SecurePass123!'])->assertOk();$this->assertGuest();
  $this->postJson('/api/v1/applicant/login/verify',['challenge_id'=>$start->json('challenge_id'),'code'=>'000000'])->assertUnprocessable();$this->assertGuest();
  $this->postJson('/api/v1/applicant/login/verify',['challenge_id'=>$start->json('challenge_id'),'code'=>$start->json('demo_code')])->assertOk()->assertJsonPath('user.account_type','applicant');$this->assertAuthenticatedAs($u);
 }
 public function test_hackathon_mode_signs_in_applicant_without_sending_sms():void{
  config()->set('portal.sms_enabled',false);
  $u=new User(['name'=>'Applicant','email'=>'applicant@example.test','password'=>'SecurePass123!']);$u->member_type='applicant';$u->phone='+26771234567';$u->save();
  $this->postJson('/api/v1/login',['email'=>$u->email,'password'=>'wrong'])->assertUnprocessable();$this->assertGuest();
  $this->postJson('/api/v1/login',['email'=>$u->email,'password'=>'SecurePass123!'])->assertOk()->assertJsonPath('user.account_type','applicant')->assertJsonMissingPath('challenge_id');
  $this->assertAuthenticatedAs($u);$this->assertDatabaseCount('account_challenges',0);
 }
}
