<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatwootBot;
use Illuminate\Http\Request;

class ChatwootWebhookController extends Controller {
 public function handle(Request $request){
  $secret=config('portal.chatwoot_webhook_secret');abort_unless($secret&&config('portal.chatwoot_bot_token')&&config('portal.chatwoot_team_id'),503);
  $timestamp=$request->header('X-Chatwoot-Timestamp');$signature=$request->header('X-Chatwoot-Signature');
  abort_unless($timestamp&&ctype_digit($timestamp)&&abs(time()-(int)$timestamp)<=300&&$signature&&hash_equals('sha256='.hash_hmac('sha256',$timestamp.'.'.$request->getContent(),$secret),$signature),401);
  ChatwootBot::handle($request->json()->all());return response()->json(['ok'=>true]);
 }
}
