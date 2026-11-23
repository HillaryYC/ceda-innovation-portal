<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache,Http};

class ChatwootWebhookTest extends \Illuminate\Foundation\Testing\TestCase {
 use RefreshDatabase;
 public function createApplication(){ $app=require __DIR__.'/../../bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();return $app; }
 private function event(int $id,string $content):array{return ['event'=>'message_created','id'=>$id,'content'=>$content,'message_type'=>'incoming','private'=>false,'account'=>['id'=>198552],'inbox'=>['id'=>158971],'conversation'=>['id'=>42,'inbox_id'=>158971,'status'=>'pending']];}
 private function send(array $event){$body=json_encode($event);$time=(string)time();return $this->call('POST','/api/v1/chatwoot/webhook',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_X_CHATWOOT_TIMESTAMP'=>$time,'HTTP_X_CHATWOOT_SIGNATURE'=>'sha256='.hash_hmac('sha256',$time.'.'.$body,'test-secret')],$body);}
 protected function setUp():void{parent::setUp();config()->set(['portal.chatwoot_account_id'=>198552,'portal.chatwoot_inbox_id'=>158971,'portal.chatwoot_team_id'=>15370,'portal.chatwoot_bot_token'=>'test-token','portal.chatwoot_webhook_secret'=>'test-secret']);Cache::flush();Http::fake(['app.chatwoot.com/*'=>Http::response(['id'=>1],200)]);}
 public function test_rejects_unsigned_requests():void{$this->postJson('/api/v1/chatwoot/webhook',$this->event(1,'How do I apply?'))->assertUnauthorized();Http::assertNothingSent();}
 public function test_sensitive_question_adds_private_reason_and_assigns_evaluators():void{
  $this->send($this->event(2,'I want to dispute a payment'))->assertOk();
  Http::assertSentCount(4);Http::assertSent(fn($r)=>str_ends_with($r->url(),'/messages')&&$r['private']===true&&str_contains($r['content'],'payment dispute'));
  Http::assertSent(fn($r)=>str_ends_with($r->url(),'/assignments')&&$r['team_id']===15370);
  $this->send($this->event(2,'I want to dispute a payment'))->assertOk();Http::assertSentCount(4);
 }
 public function test_second_no_escalates():void{
  $this->send($this->event(3,'no'))->assertOk();$this->send($this->event(4,'No'))->assertOk();
  Http::assertSent(fn($r)=>str_ends_with($r->url(),'/messages')&&$r['private']===true&&str_contains($r['content'],'said no twice'));
 }
 public function test_unknown_question_escalates_as_low_confidence():void{
  $this->send($this->event(5,'Can you explain quantum telepathy?'))->assertOk();
  Http::assertSent(fn($r)=>str_ends_with($r->url(),'/messages')&&$r['private']===true&&str_contains($r['content'],'Low confidence'));
 }
}
