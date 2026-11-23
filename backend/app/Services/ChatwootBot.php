<?php
namespace App\Services;

use Illuminate\Support\Facades\{Cache,Http};

class ChatwootBot {
 private static function base():string{return 'https://app.chatwoot.com/api/v1/accounts/'.config('portal.chatwoot_account_id');}
 private static function send(int $conversation,array $body):void{
  Http::withToken(config('portal.chatwoot_bot_token'))->timeout(15)->post(self::base()."/conversations/$conversation/messages",$body)->throw();
 }
 private static function reason(string $question):?string{
  $text=mb_strtolower($question);
  foreach([
   'payment dispute'=>'/\b(payment|paid|charge|refund|invoice|transaction|money deducted|bank transfer)\b/u',
   'rejected application'=>'/\b(reject(?:ed|ion)?|declin(?:ed|e)|appeal|unsuccessful application)\b/u',
   'fraud or misconduct'=>'/\b(fraud|bribery|corruption|scam|misconduct)\b/u',
   'private account matter'=>'/\b(identity number|omang|password|verification code|otp|account compromised|stolen account)\b/u',
  ] as $reason=>$pattern)if(preg_match($pattern,$text))return 'Sensitive topic: '.$reason;
  return null;
 }
 public static function handle(array $event):void{
  if(($event['event']??null)!=='message_created'||!in_array($event['message_type']??null,['incoming',0,'0'],true)||($event['private']??false)||($event['conversation']['status']??null)!=='pending')return;
  if((int)($event['account']['id']??0)!==(int)config('portal.chatwoot_account_id')||(int)($event['inbox']['id']??$event['conversation']['inbox_id']??0)!==(int)config('portal.chatwoot_inbox_id'))return;
  $conversation=(int)($event['conversation']['id']??0);$message=(int)($event['id']??0);$question=trim((string)($event['content']??''));
  if(!$conversation||!$message||$question==='')return;
  $lock=Cache::lock("chatwoot-conversation:$conversation",30);
  $lock->block(5,function()use($conversation,$message,$question){
   if(Cache::has("chatwoot-message:$message"))return;
   $no=preg_match('/^(no|nope|not helpful|that did not help)[.!?\s]*$/iu',$question)===1;
   $count=$no?(int)Cache::get("chatwoot-no:$conversation",0)+1:0;
   $reason=self::reason($question);
   if(!$reason&&$count>=2)$reason='Applicant said no twice';
   $reply=null;
   if(!$reason&&$no)$reply=['answer'=>'Sorry that did not help. Please tell me what you need clarified, or say no again and I will connect you with an evaluator.','confidence'=>'high'];
   if(!$reason&&!$reply)$reply=PublicGuidance::reply($question);
   if(!$reason&&($reply['confidence']??'low')==='low')$reason='Low confidence: no confirmed guidance matched';
   if($reason){
    self::send($conversation,['content'=>'Escalation reason: '.$reason.'. Review the applicant question above before replying.','message_type'=>'outgoing','private'=>true]);
    Http::withToken(config('portal.chatwoot_bot_token'))->timeout(15)->post(self::base()."/conversations/$conversation/toggle_status",['status'=>'open'])->throw();
    Http::withToken(config('portal.chatwoot_bot_token'))->timeout(15)->post(self::base()."/conversations/$conversation/assignments",['team_id'=>(int)config('portal.chatwoot_team_id')])->throw();
    self::send($conversation,['content'=>'I have sent your question to an evaluator. They will reply here when available.','message_type'=>'outgoing','private'=>false]);
   }else self::send($conversation,['content'=>$reply['answer'],'message_type'=>'outgoing','private'=>false]);
   Cache::put("chatwoot-no:$conversation",$count,now()->addHours(24));Cache::put("chatwoot-message:$message",true,now()->addDays(2));
  });
 }
}
