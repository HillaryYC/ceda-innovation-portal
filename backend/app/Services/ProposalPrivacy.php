<?php
namespace App\Services;
use App\Models\{Idea,User};
class ProposalPrivacy {
 public static function clean(string $text,?Idea $idea=null):string{
  $identifiers=[];if($idea){$u=User::find($idea->applicant_id);$identifiers=array_filter([$idea->team,$idea->location,$u?->name,$u?->email,$u?->phone]);if($u)foreach(preg_split('/\s+/u',$u->name) as $part)if(mb_strlen($part)>=3)$identifiers[]=$part;}
  foreach($identifiers as $value)$text=preg_replace('/'.preg_quote($value,'/').'/iu','[REDACTED]',$text);
  $text=preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i','[EMAIL REDACTED]',$text);
  $text=preg_replace('/(?<!\w)\+?\d[\d\s()\-]{6,}\d(?!\w)/','[NUMBER REDACTED]',$text);
  $text=preg_replace('/https?:\/\/\S+/i','[LINK REDACTED]',$text);
  return $text;
 }
 public static function proposal(Idea $idea):array{
  $answers=$idea->form_answers??[];$schema=$idea->form_schema??[];$fields=['summary'=>$idea->summary,'problem'=>$idea->problem,'solution'=>$idea->solution,'customers'=>$answers['customers']??''];
  foreach(FormCatalog::questions($schema,$idea->category,$answers['business_stage']??null) as $q){if(($q['type']??'')==='file'||preg_match('/contact|name|email|phone|address|identity|passport|omang|account/i',$q['key'].' '.$q['label']))continue;$fields['question_'.$q['key']]=$answers['category_answers'][$idea->category][$q['key']]??'';}
  return ['category'=>$idea->category,'development_stage'=>$answers['business_stage']??'',...array_map(fn($s)=>self::clean(is_array($s)?implode(', ',$s):(string)$s,$idea),$fields)];
 }
}
