<?php
namespace App\Jobs;
use Illuminate\Contracts\Queue\{ShouldQueue,ShouldBeEncrypted};use Illuminate\Foundation\Queue\Queueable;use Illuminate\Support\Facades\{Mail,Http,DB};
class DeliverAccountCode implements ShouldQueue,ShouldBeEncrypted{
 use Queueable;public int $tries=3;public function __construct(public string $id,public string $destination,public string $channel,public string $code){}
 public function handle():void{$challenge=DB::table('account_challenges')->where('id',$this->id)->first();if(!$challenge||$challenge->used_at||now()->gte($challenge->expires_at))return;$message="Your CEDA verification code is {$this->code}. It expires in 10 minutes. If you did not request it, ignore this message.";if($this->channel==='email'){Mail::raw($message,fn($m)=>$m->to($this->destination)->subject('CEDA account verification'));}else{Http::asForm()->withBasicAuth(config('portal.sms_sid'),config('portal.sms_token'))->timeout(15)->post('https://api.twilio.com/2010-04-01/Accounts/'.config('portal.sms_sid').'/Messages.json',['To'=>$this->destination,'From'=>config('portal.sms_from'),'Body'=>$message])->throw();}}
}
