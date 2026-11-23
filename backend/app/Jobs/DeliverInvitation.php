<?php
namespace App\Jobs;
use Illuminate\Contracts\Queue\{ShouldQueue,ShouldBeEncrypted};
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\{DB,Mail};
class DeliverInvitation implements ShouldQueue,ShouldBeEncrypted {
 use Queueable;public int $tries=3;public function backoff():array{return [30,120,300];}
 public function __construct(public int $userId,public string $token){}
 public function handle():void{$invite=DB::table('staff_invitations')->where('token_hash',hash('sha256',$this->token))->first();if(!$invite||$invite->delivery_status==='sent'||$invite->accepted_at||$invite->revoked_at||now()->gte($invite->expires_at))return;$user=\App\Models\User::findOrFail($this->userId);$url=url('/activate?token='.$this->token);Mail::raw("You have been invited to the CEDA Innovation Portal. Activate your account: $url",fn($m)=>$m->to($user->email)->subject('Activate your CEDA account'));DB::table('staff_invitations')->where('id',$invite->id)->update(['delivery_status'=>'sent','delivery_error'=>null,'updated_at'=>now()]);}
 public function failed(?\Throwable $e):void{DB::table('staff_invitations')->where('token_hash',hash('sha256',$this->token))->update(['delivery_status'=>'failed','delivery_error'=>'Delivery failed. Ask an administrator to resend.','updated_at'=>now()]);}
}
