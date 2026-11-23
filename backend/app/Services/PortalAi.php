<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
class PortalAi{
 public static function answer(string $instructions,string $input):string{
  abort_unless(config('portal.ai_key'),503,'AI is not configured yet. Your proposal has not been sent.');
  $response=Http::withToken(config('portal.ai_key'))->timeout(45)->post('https://api.openai.com/v1/responses',['model'=>config('portal.ai_model'),'store'=>false,'instructions'=>$instructions,'input'=>$input,'max_output_tokens'=>1000]);
  abort_unless($response->successful(),503,'AI is temporarily unavailable. Please try again later.');$text=collect($response->json('output',[]))->flatMap(fn($o)=>$o['content']??[])->where('type','output_text')->pluck('text')->implode("\n");abort_if(!$text,503,'AI did not return a usable answer.');return $text;
 }
}
