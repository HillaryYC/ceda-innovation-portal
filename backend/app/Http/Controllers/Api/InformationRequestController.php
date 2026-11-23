<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Idea;
use App\Services\Administration;
use App\Services\ProgrammeAccess;
use App\Services\AdjudicationReadiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InformationRequestController extends Controller {
 public static function forIdea(int $ideaId):\Illuminate\Support\Collection{
  return DB::table('information_requests as ir')->leftJoin('application_attachments as f','f.id','=','ir.attachment_id')->where('ir.idea_id',$ideaId)->orderByDesc('ir.id')->get(['ir.id','ir.source','ir.question','ir.status','ir.response','ir.created_at','ir.responded_at','f.id as attachment_id','f.name as attachment_name','f.mime as attachment_mime','f.size as attachment_size']);
 }
 public function list(Request $request,int $assignment,ProgrammeAccess $access){
  $review=DB::table('review_assignments as a')->join('ideas as i','i.id','=','a.idea_id')->where('a.id',$assignment)->where('a.user_id',$request->user()->id)->where('a.active',true)->select('a.idea_id','a.kind','i.programme_id')->first();
  abort_unless($review&&$access->allows($request->user(),$review->programme_id,$review->kind==='evaluation'?'reviews.evaluate':'reviews.adjudicate'),403);
  abort_if($review->kind==='adjudication'&&!AdjudicationReadiness::ready($review->idea_id),403);
  return ['data'=>self::forIdea($review->idea_id)];
 }
 public function create(Request $request,int $assignment,ProgrammeAccess $access){
  $data=$request->validate(['question'=>'required|string|min:10|max:5000']);
  return DB::transaction(function()use($request,$assignment,$access,$data){
   $review=DB::table('review_assignments as a')->join('ideas as i','i.id','=','a.idea_id')->where('a.id',$assignment)->where('a.user_id',$request->user()->id)->where('a.active',true)->whereIn('a.kind',['evaluation','adjudication'])->select('a.id','a.idea_id','a.kind','a.status','i.programme_id','i.applicant_id')->lockForUpdate()->first();
   abort_unless($review&&$access->allows($request->user(),$review->programme_id,$review->kind==='evaluation'?'reviews.evaluate':'reviews.adjudicate'),403);
   abort_if($review->kind==='adjudication'&&!AdjudicationReadiness::ready($review->idea_id),403);
   abort_if($review->status==='submitted',409,'A submitted review cannot request more information.');
   abort_unless($review->applicant_id,422,'This application has no applicant account to respond.');
   abort_if(DB::table('information_requests')->where('idea_id',$review->idea_id)->where('status','open')->exists(),409,'An information request is already awaiting the applicant.');
   $id=DB::table('information_requests')->insertGetId(['idea_id'=>$review->idea_id,'assignment_id'=>$review->id,'requested_by'=>$request->user()->id,'source'=>$review->kind,'question'=>trim($data['question']),'status'=>'open','created_at'=>now(),'updated_at'=>now()]);
   $idea=Idea::findOrFail($review->idea_id);$idea->status='information';$idea->information_request=trim($data['question']);$idea->save();
   DB::table('portal_notifications')->updateOrInsert(['user_id'=>$review->applicant_id,'idea_id'=>$review->idea_id,'kind'=>'information_requested'],['programme_id'=>$review->programme_id,'title'=>'More information needed','message'=>'CEDA has asked a question about IDEA-'.$review->idea_id.'. Open your application to respond.','read_at'=>null,'created_at'=>now(),'updated_at'=>now()]);
   Administration::audit($request->user(),'information.requested','idea',$review->idea_id,['request_id'=>$id],$review->programme_id);
   return response()->json(['id'=>$id,'status'=>'open'],201);
  });
 }
 public function respond(Request $request,Idea $idea){
  abort_unless($request->user()->member_type==='applicant'&&$idea->applicant_id===$request->user()->id,404);
  $data=$request->validate(['response'=>'required|string|min:10|max:10000','file'=>'sometimes|array','file.name'=>'required_with:file|string|max:200','file.content'=>'required_with:file|string|max:7000000']);
  return DB::transaction(function()use($request,$idea,$data){
   $row=DB::table('information_requests')->where('idea_id',$idea->id)->where('status','open')->lockForUpdate()->latest('id')->first();abort_unless($row,409,'There is no open information request for this application.');
   $attachment=null;
   if(isset($data['file'])){
    $name=basename(str_replace('\\','/',$data['file']['name']));$extension=strtolower(pathinfo($name,PATHINFO_EXTENSION));
    $bytes=base64_decode($data['file']['content'],true);$size=$bytes===false?0:strlen($bytes);
    $mime=$size?(new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes):'';
    if($extension==='xlsx'&&$size){
     $path=tempnam(sys_get_temp_dir(),'ceda-xlsx-');file_put_contents($path,$bytes);$zip=new \ZipArchive();
     try{$opened=$zip->open($path)===true;$valid=$opened&&$zip->numFiles<=512&&$zip->locateName('[Content_Types].xml')!==false&&$zip->locateName('xl/workbook.xml')!==false;if($opened){$expanded=0;for($i=0;$i<$zip->numFiles;$i++){$expanded+=($zip->statIndex($i)['size']??0);if($expanded>20*1024*1024){$valid=false;break;}}$zip->close();}}finally{unlink($path);}
     if($valid)$mime='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }
    if(!$size||$size>5*1024*1024||!(($extension==='pdf'&&$mime==='application/pdf')||($extension==='xlsx'&&$mime==='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')))throw \Illuminate\Validation\ValidationException::withMessages(['file'=>'Choose a PDF or Excel (.xlsx) file up to 5 MB.']);
    $attachment=$idea->attachments()->create(['name'=>$name,'mime'=>$mime,'size'=>$size,'content'=>base64_encode($bytes),'kind'=>'information_response']);
   }
   DB::table('information_requests')->where('id',$row->id)->update(['response'=>trim($data['response']),'attachment_id'=>$attachment?->id,'status'=>'responded','responded_at'=>now(),'updated_at'=>now()]);
   $idea->status='assigned';$idea->information_request=null;$idea->save();
   DB::table('portal_notifications')->where('user_id',$request->user()->id)->where('idea_id',$idea->id)->where('kind','information_requested')->update(['read_at'=>now(),'updated_at'=>now()]);
   DB::table('portal_notifications')->updateOrInsert(['user_id'=>$row->requested_by,'idea_id'=>$idea->id,'kind'=>'information_response'],['programme_id'=>$idea->programme_id,'title'=>'Applicant responded','message'=>'The applicant answered your information request for IDEA-'.$idea->id.'.','read_at'=>null,'created_at'=>now(),'updated_at'=>now()]);
   Administration::audit($request->user(),'information.responded','idea',$idea->id,['request_id'=>$row->id],$idea->programme_id);
   return ['status'=>'responded','attachment'=>$attachment?->only(['id','name','size','mime'])];
  });
 }
}
