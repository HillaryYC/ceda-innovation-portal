<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{User,Idea};
use App\Services\Administration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,DB};
class ApplicantController extends Controller {
 public function register(Request $r){$r->merge(['email'=>strtolower(trim((string)$r->input('email')))]);
  $phone=trim((string)$r->input('phone'));
  if(preg_match('/^[0-9]{8}$/',$phone))$r->merge(['phone'=>'+267'.$phone]);
  $d=$r->validate(['name'=>'required|string|max:150','email'=>'required|email|max:254|unique:users,email','password'=>['required','confirmed','string','min:8','max:12','regex:/[a-z]/','regex:/[A-Z]/','regex:/[0-9]/','regex:/[^A-Za-z0-9]/'],'confidentiality'=>'accepted','phone'=>'required|regex:/^\+267[0-9]{8}$/|unique:users,phone']);
  $u=new User(['name'=>$d['name'],'email'=>strtolower($d['email']),'password'=>$d['password']]);$u->member_type='applicant';$u->phone=$d['phone']??null;$u->save();
  Administration::audit($u,'applicant.registered','user',$u->id);return response()->json(['message'=>config('portal.sms_enabled')?'Account created. Sign in to verify your mobile number.':'Account created. Sign in with your email and password.'],201);
 }
 private function applicant(Request $r):void{abort_unless($r->user()->member_type==='applicant',403);}
 public function index(Request $r){$this->applicant($r);$ideas=Idea::with('attachments')->where('applicant_id',$r->user()->id)->latest('id')->get();$requests=DB::table('information_requests as ir')->leftJoin('application_attachments as f','f.id','=','ir.attachment_id')->whereIn('ir.idea_id',$ideas->pluck('id'))->orderByDesc('ir.id')->get(['ir.id','ir.idea_id','ir.source','ir.question','ir.status','ir.response','ir.created_at','ir.responded_at','f.id as attachment_id','f.name as attachment_name','f.mime as attachment_mime','f.size as attachment_size'])->groupBy('idea_id');foreach($ideas as $idea)$idea->information_requests=$requests->get($idea->id,collect());return ['data'=>$ideas,'programmes'=>DB::table('programmes')->get(['id','name']),'form_definition'=>\App\Services\FormCatalog::current((int)DB::table('programmes')->value('id')),'form_definitions'=>DB::table('programmes')->pluck('id')->mapWithKeys(fn($id)=>[$id=>\App\Services\FormCatalog::current($id)])];}
 public function deleteDraft(Request $r,int $id){
  $this->applicant($r);
  DB::transaction(function()use($r,$id){
   $idea=Idea::where('applicant_id',$r->user()->id)->lockForUpdate()->findOrFail($id);
   abort_unless($idea->status==='draft',409,'Only drafts can be deleted.');
   DB::table('portal_notifications')->where('idea_id',$idea->id)->delete();
   $idea->delete();
   Administration::audit($r->user(),'draft.deleted','idea',$id,[],$idea->programme_id);
  });
  return response()->noContent();
 }
 public function save(Request $r,?int $id=null){
  $this->applicant($r);
  $result=DB::transaction(function()use($r,$id){
   $idea=$id?Idea::where('applicant_id',$r->user()->id)->lockForUpdate()->findOrFail($id):new Idea;
   abort_if($id&&$idea->status!=='draft',409,'Only drafts can be edited.');if($id)abort_unless((int)$r->input('programme_id')===$idea->programme_id,422,'Start a new draft to apply to another programme.');
   $schema=$idea->form_schema??\App\Services\FormCatalog::current((int)$r->input('programme_id'));$submitting=$r->boolean('submit');
   $d=$r->validate([
    'title'=>($submitting?'required':'nullable').'|string|max:200',
    'category'=>['required',\Illuminate\Validation\Rule::in(array_keys($schema['categories']))],
    'location'=>($submitting?'required':'nullable').'|string|max:150','programme_id'=>'required|exists:programmes,id',
    'summary'=>($submitting?'required':'nullable').'|string|max:3000',
    'problem'=>'nullable|string|max:10000','solution'=>'nullable|string|max:10000',
    'submit'=>'required|boolean','draft_step'=>'sometimes|integer|min:0|max:'.(($schema['layout']??null)==='smart-v1'?4:2),
    'form_version'=>['required',\Illuminate\Validation\Rule::in([$schema['version']])],
    'form_answers'=>'present|array','form_answers.customers'=>'nullable|string|max:3000',
    'form_answers.business_stage'=>['nullable',\Illuminate\Validation\Rule::in($schema['stages'])],
    'form_answers.contact_phone'=>'nullable|string|max:40','form_answers.category_answers'=>'sometimes|array','form_answers.core_answers'=>'sometimes|array',
    'declaration'=>$submitting?'accepted':'sometimes|boolean',
   ]);
   if($submitting&&($schema['mode']??null)==='document'&&!$idea->attachments()->where('kind','proposal')->where('category',$d['category'])->where('template_version',$schema['version'])->exists())throw \Illuminate\Validation\ValidationException::withMessages(['proposal'=>'Upload a completed Word or PDF proposal for the selected category before submitting.']);
   $answers=$d['form_answers']??[];$categoryAnswers=[];if($submitting&&($schema['mode']??null)==='online'){$r->validate(['problem'=>'required|string|max:10000','solution'=>'required|string|max:10000','form_answers.customers'=>'required|string|max:3000','form_answers.business_stage'=>['required',\Illuminate\Validation\Rule::in($schema['stages'])]]);}
   $coreAnswers=[];
   if(($schema['layout']??null)==='smart-v1'){
    foreach($schema['core'] as $section=>$questions)foreach($questions as $q){
     if($q['key']==='team_size'&&$r->input('form_answers.core_answers.applying_as')==='Individual')continue;
     $key='form_answers.core_answers.'.$q['key'];$value=$r->input($key);$required=$submitting&&($q['required']??false);
     if(($q['type']??'')==='multiselect'){$rules=[$required?'required':'nullable','array'];$r->validate([$key=>$rules,$key.'.*'=>['string',\Illuminate\Validation\Rule::in($q['options']??[])]]);}
     else{$rules=[$required?'required':'nullable',($q['type']??'')==='number'?'numeric':'string'];if(($q['type']??'')==='number')$rules[]='min:'.($q['key']==='amount_requested'?1:0);else $rules[]='max:'.$q['max'];if(($q['type']??'')==='select')$rules[]=\Illuminate\Validation\Rule::in($q['options']??[]);$r->validate([$key=>$rules]);}
     if($value!==null)$coreAnswers[$q['key']]=$value;
    }
    if($submitting){$amount=(float)($coreAnswers['amount_requested']??0);$uses=0;foreach(['use_equipment','use_stock','use_premises','use_working_capital','use_marketing','use_other'] as $key)$uses+=(float)($coreAnswers[$key]??0);if(abs($amount-$uses)>0.01)throw \Illuminate\Validation\ValidationException::withMessages(['funding'=>'The use-of-funds amounts must add up to the amount requested.']);}
   }
   foreach($schema['categories'] as $category=>$definition){
    foreach($definition['questions'] as $q){
     $key='form_answers.category_answers.'.$category.'.'.$q['key'];
     $relevant=empty($q['stages'])||in_array($answers['business_stage']??null,$q['stages'],true);$required=$submitting&&($schema['mode']??null)==='online'&&$category===$d['category']&&$relevant&&($q['required']??false);$type=$q['type']??'textarea';
     if($type==='multiselect')$r->validate([$key=>[$required?'required':'nullable','array'],$key.'.*'=>['string',\Illuminate\Validation\Rule::in($q['options']??[])]]);
     else{$rules=[$required?'required':'nullable',$type==='number'?'numeric':'string'];if($type==='number')$rules[]='min:0';else $rules[]='max:'.$q['max'];if($submitting&&$category===$d['category']&&$relevant){if($type==='select')$rules[]=\Illuminate\Validation\Rule::in($q['options']??[]);if($type==='file'&&$r->input($key))abort_unless($idea->exists&&$idea->attachments()->where('id',$r->input($key))->where('kind','supporting')->exists(),422,'Upload the required supporting document.');}$r->validate([$key=>$rules]);}
     if((!$submitting||($category===$d['category']&&$relevant))&&$r->input($key)!==null)$categoryAnswers[$category][$q['key']]=$r->input($key);
    }
   }
   $answers=collect($answers)->only(['customers','business_stage','contact_phone'])->all();
   if(($schema['layout']??null)==='smart-v1')$answers['core_answers']=$coreAnswers;
   $answers['category_answers']=$submitting?[$d['category']=>$categoryAnswers[$d['category']]??[]]:$categoryAnswers;
   $idea->fill(collect($d)->only(['title','category','location','programme_id','summary','problem','solution'])->all());
   foreach(['title','summary','problem','solution','location'] as $field)$idea->$field=$d[$field]??'';
   $idea->applicant_id=$r->user()->id;$idea->team=$r->user()->name;$idea->initial='';$idea->color='#fff';$idea->ink='#000';
   $idea->submitted_at=$submitting?now():null;$idea->checks=[];$idea->complete=$submitting?100:0;$idea->status=$submitting?'ready':'draft';$idea->allocation_wait_reason=$submitting?'Waiting for automatic evaluator allocation.':null;
   $idea->form_version=$schema['version'];$idea->form_schema=$schema;$idea->form_answers=$answers;$idea->draft_step=$d['draft_step']??0;$idea->declaration_at=$submitting?now():null;$idea->save();
   Administration::audit($r->user(),$submitting?'application.submitted':'draft.saved','idea',$idea->id,[],$idea->programme_id);
   return ['data'=>$idea->load('attachments')];
  });
  if($r->boolean('submit')){try{(new \App\Services\AutoAllocator)->assign($result['data']->id);}catch(\Throwable $error){report($error);Idea::whereKey($result['data']->id)->update(['allocation_wait_reason'=>'Automatic allocation needs attention.']);}$result['data']=$result['data']->fresh()->load('attachments');}
  return $result;
 }
 public function upload(Request $r,int $id){
  $this->applicant($r);$d=$r->validate(['name'=>'required|string|max:200','content'=>'required|string|max:7000000','kind'=>'sometimes|in:proposal,supporting','category'=>'required_if:kind,proposal|string|max:100','template_version'=>'required_if:kind,proposal|string|max:100']);
  return DB::transaction(function()use($r,$id,$d){
   $idea=Idea::where('applicant_id',$r->user()->id)->lockForUpdate()->findOrFail($id);abort_unless($idea->status==='draft',409,'Only drafts accept attachments.');
   $bytes=base64_decode($d['content'],true);$size=$bytes===false?0:strlen($bytes);
   $mime=$size?(new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes):'';
   $ext=strtolower(pathinfo($d['name'],PATHINFO_EXTENSION));
   $kind=$d['kind']??'supporting';$schema=($idea->form_schema['mode']??null)==='document'?$idea->form_schema:config('application_form');
   $types=['application/pdf'=>['pdf'],'image/jpeg'=>['jpg','jpeg'],'image/png'=>['png']];
   $isDocx=$ext==='docx'&&$this->validDocx($bytes?:'');
   if($isDocx)$mime='application/vnd.openxmlformats-officedocument.wordprocessingml.document';
   if($isDocx)$types['application/vnd.openxmlformats-officedocument.wordprocessingml.document']=['docx'];
   if(!$size||$size>5*1024*1024||!isset($types[$mime])||!in_array($ext,$types[$mime])||($kind==='proposal'&&!in_array($ext,['pdf','docx'])))throw \Illuminate\Validation\ValidationException::withMessages(['file'=>$kind==='proposal'?'Choose a Word (.docx) or PDF proposal of up to 5 MB.':'Choose a PDF, Word (.docx), JPG or PNG file of up to 5 MB.']);
   if($kind==='proposal'&&(($d['category']??null)!==$idea->category||($d['template_version']??null)!==$schema['version']))throw \Illuminate\Validation\ValidationException::withMessages(['file'=>'Save your selected category before uploading its proposal.']);
   $existing=$kind==='proposal'?$idea->attachments()->where('kind','proposal')->first():null;
   if(($kind==='supporting'&&$idea->attachments()->where('kind','supporting')->count()>=3)||$idea->attachments()->count()-($existing?1:0)>=4||$idea->attachments()->sum('size')-($existing?->size??0)+$size>20*1024*1024)throw \Illuminate\Validation\ValidationException::withMessages(['file'=>'Attach one proposal and up to 3 supporting files, with a combined size of 20 MB.']);
   $file=$idea->attachments()->create(['name'=>basename(str_replace('\\','/',$d['name'])),'mime'=>$mime,'size'=>$size,'content'=>base64_encode($bytes),'kind'=>$kind,'category'=>$kind==='proposal'?$d['category']:null,'template_version'=>$kind==='proposal'?$schema['version']:null]);
   $existing?->delete();
   Administration::audit($r->user(),'attachment.uploaded','idea',$idea->id,[],$idea->programme_id);return ['data'=>$file];
  });
 }
 private function validDocx(string $bytes):bool{
  $path=tempnam(sys_get_temp_dir(),'ceda-docx-');$zip=new \ZipArchive;
  try{
   file_put_contents($path,$bytes);if($zip->open($path)!==true)return false;
   if($zip->numFiles>512||$zip->locateName('[Content_Types].xml')===false||$zip->locateName('word/document.xml')===false){$zip->close();return false;}
   $total=0;for($i=0;$i<$zip->numFiles;$i++){$stat=$zip->statIndex($i);$total+=$stat['size'];if($total>50*1024*1024||str_contains(strtolower($stat['name']),'vbaproject')){$zip->close();return false;}}
   $types=$zip->getFromName('[Content_Types].xml');$ok=is_string($types)&&str_contains($types,'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml');$zip->close();return $ok;
  }finally{if(is_file($path))unlink($path);}
 }
 public function template(Request $r,string $slug){
  $this->applicant($r);$schema=config('application_form');
  abort_unless(collect($schema['categories'])->contains(fn($c)=>$c['template_slug']===$slug),404);
  $version=$r->query('version',$schema['version']);abort_unless($version===$schema['version'],404);
  $path=resource_path('proposal-templates/'.$version.'/'.$slug.'.docx');abort_unless(is_file($path),404);
  return response()->download($path,'CEDA-'.$slug.'-proposal-template.docx',['Cache-Control'=>'private, no-store']);
 }
 public function removeAttachment(Request $r,int $id,int $file){
  $this->applicant($r);return DB::transaction(function()use($r,$id,$file){
   $idea=Idea::where('applicant_id',$r->user()->id)->lockForUpdate()->findOrFail($id);abort_unless($idea->status==='draft',409,'Submitted attachments cannot be removed.');
   $idea->attachments()->findOrFail($file)->delete();return response()->noContent();
  });
 }
 public function download(Request $r,\App\Models\ApplicationAttachment $attachment,\App\Services\ProgrammeAccess $access){
  $idea=Idea::findOrFail($attachment->idea_id);$u=$r->user();
  $owner=$u->member_type==='applicant'&&$idea->applicant_id===$u->id;
  $kinds=[];if($access->allows($u,$idea->programme_id,'reviews.evaluate'))$kinds[]='evaluation';if($access->allows($u,$idea->programme_id,'reviews.adjudicate'))$kinds[]='adjudication';
  $staff=$u->member_type!=='applicant'&&$idea->status!=='draft'&&($access->allows($u,$idea->programme_id,'applications.view')||DB::table('review_assignments')->where('idea_id',$idea->id)->where('user_id',$u->id)->whereIn('kind',$kinds)->where('active',true)->where(function($q)use($idea){$q->where('kind','evaluation');if(\App\Services\AdjudicationReadiness::ready($idea->id))$q->orWhere('kind','adjudication');})->exists());
  abort_unless($owner||$staff,404);
  return response(base64_decode($attachment->content))->header('Content-Type',$attachment->mime)->header('Content-Disposition',\Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition('attachment',$attachment->name,'attachment.'.pathinfo($attachment->name,PATHINFO_EXTENSION)))->header('Cache-Control','private, no-store')->header('X-Content-Type-Options','nosniff');
 }
 public function preview(Request $r,\App\Models\ApplicationAttachment $attachment,\App\Services\ProgrammeAccess $access){
  $response=$this->download($r,$attachment,$access);
  $mime=$attachment->mime;
  if(str_contains($mime,'wordprocessingml')||str_ends_with(strtolower($attachment->name),'.docx')){
   $temp=tempnam(sys_get_temp_dir(),'ceda-preview-');file_put_contents($temp,$response->getContent());$zip=new \ZipArchive();$xml='';
   if($zip->open($temp)===true){$xml=$zip->getFromName('word/document.xml')?:'';$zip->close();}unlink($temp);
   abort_if($xml==='',422,'This document cannot be previewed.');$document=new \DOMDocument();$document->loadXML($xml,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
   $xpath=new \DOMXPath($document);$xpath->registerNamespace('w','http://schemas.openxmlformats.org/wordprocessingml/2006/main');$paragraphs=[];
   foreach($xpath->query('//w:body//w:p') as $paragraph){$line='';foreach($xpath->query('.//w:t',$paragraph) as $text)$line.=$text->textContent;if(trim($line)!=='')$paragraphs[]=$line;}
   $response->setContent(implode("\n\n",$paragraphs));$mime='text/plain; charset=UTF-8';
  }
  if(str_ends_with(strtolower($attachment->name),'.xlsx')){
   $temp=tempnam(sys_get_temp_dir(),'ceda-preview-');file_put_contents($temp,$response->getContent());$zip=new \ZipArchive();$lines=[];
   try{abort_unless($zip->open($temp)===true,422,'This spreadsheet cannot be previewed.');$shared=[];$strings=$zip->getFromName('xl/sharedStrings.xml');if($strings){$xml=new \DOMDocument();$xml->loadXML($strings,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);foreach($xml->getElementsByTagName('si') as $item)$shared[]=trim($item->textContent);}$sheet=$zip->getFromName('xl/worksheets/sheet1.xml');abort_unless($sheet,422,'This spreadsheet cannot be previewed.');$xml=new \DOMDocument();$xml->loadXML($sheet,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);foreach($xml->getElementsByTagName('row') as $row){$cells=[];foreach($row->getElementsByTagName('c') as $cell){$raw=$cell->getElementsByTagName('v')->item(0)?->textContent??$cell->getElementsByTagName('is')->item(0)?->textContent??'';$cells[]=$cell->getAttribute('t')==='s'?($shared[(int)$raw]??''):$raw;}if($cells)$lines[]=implode(' | ',$cells);if(count($lines)>=200)break;}$zip->close();}finally{unlink($temp);}
   $response->setContent(implode("\n",$lines));$mime='text/plain; charset=UTF-8';
  }
  abort_unless(in_array($mime,['application/pdf','image/jpeg','image/png'],true)||str_starts_with($mime,'text/plain'),415,'Preview is unavailable for this file type.');
  return $response->header('Content-Type',$mime)->header('Content-Disposition','inline')->header('Cache-Control','private, no-store')->header('X-Content-Type-Options','nosniff');
 }
}
