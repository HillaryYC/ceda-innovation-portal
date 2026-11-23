<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\ProgrammeAccess;
use App\Services\{AdjudicationReadiness,PortalNotifications};
use App\Models\Idea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class EvaluatorController extends Controller {
 public function index(Request $r,ProgrammeAccess $access){
  $ids=$access->programmes($r->user(),'reviews.evaluate');abort_unless($ids,403);
  $rows=DB::table('review_assignments as a')->join('ideas as i','i.id','=','a.idea_id')->join('review_templates as t','t.id','=','a.template_id')
   ->where('a.user_id',$r->user()->id)->where('a.kind','evaluation')->where('a.active',true)->whereIn('i.programme_id',$ids)
   ->select('a.id','a.idea_id','a.status','a.due_date','a.is_lead','a.marks','a.overall_comment','a.review_notes','a.recommendation','a.completed_sections','i.title','i.team as applicant','i.category','i.summary','i.problem','i.solution','i.form_answers','i.form_schema','t.name as template','t.criteria')->orderByDesc('a.id')->get();
  foreach($rows as $row){$row->form_answers=$row->form_answers?json_decode($row->form_answers,true):null;$row->form_schema=$row->form_schema?json_decode($row->form_schema,true):null;$row->marks=$row->marks?json_decode($row->marks,true):[];$row->review_notes=$row->review_notes?json_decode($row->review_notes,true):[];$row->completed_sections=$row->completed_sections?json_decode($row->completed_sections,true):[];$row->attachments=\App\Models\ApplicationAttachment::where('idea_id',$row->idea_id)->get();$row->information_requests=InformationRequestController::forIdea($row->idea_id);}
  return response()->json(['data'=>$rows])->header('Cache-Control','no-store');
 }
 public function save(Request $r,int $assignment,ProgrammeAccess $access){
  $row=DB::table('review_assignments as a')->join('ideas as i','i.id','=','a.idea_id')->join('review_templates as t','t.id','=','a.template_id')->where('a.id',$assignment)->where('a.user_id',$r->user()->id)->where('a.kind','evaluation')->where('a.active',true)->select('a.*','i.programme_id','t.criteria')->first();
  abort_unless($row&&$access->allows($r->user(),$row->programme_id,'reviews.evaluate'),403);
  abort_if($row->status==='submitted',409,'This evaluation has already been submitted.');
  $d=$r->validate(['marks'=>'required|array','marks.*.score'=>'nullable|numeric|min:0','marks.*.comment'=>'nullable|string|max:5000','overall_comment'=>'nullable|string|max:10000','review_notes'=>'nullable|array|max:200','review_notes.*.criterion'=>'required|integer|min:0','review_notes.*.section'=>'required|string|max:100','review_notes.*.kind'=>'required|in:Highlight,Comment,Flag,Evidence','review_notes.*.quote'=>'nullable|string|max:1000','review_notes.*.text'=>'required|string|max:5000','completed_sections'=>'nullable|array|max:5','completed_sections.*'=>'integer|between:0,4','recommendation'=>'nullable|in:fund,conditions,decline','submit'=>'required|boolean']);
  $criteria=json_decode($row->criteria,true);$marks=[];
  foreach($criteria as $index=>$criterion){$mark=$d['marks'][$index]??[];$score=$mark['score']??null;$comment=trim($mark['comment']??'');abort_if($score!==null&&$score>$criterion['max'],422,'A score exceeds its criterion maximum.');if($d['submit'])abort_if($score===null,422,'Score every criterion before submitting.');$marks[]=['score'=>$score,'comment'=>$comment];}
  if($d['submit'])abort_if(trim($d['overall_comment']??'')===''||empty($d['recommendation']),422,'Add an overall assessment and recommendation before submitting.');
  foreach($d['review_notes']??[] as $note)abort_if($note['criterion']>=count($criteria),422,'A note references an invalid criterion.');
  DB::table('review_assignments')->where('id',$assignment)->update(['marks'=>json_encode($marks),'overall_comment'=>trim($d['overall_comment']??''),'review_notes'=>json_encode($d['review_notes']??[]),'completed_sections'=>json_encode(array_values(array_unique($d['completed_sections']??[]))),'recommendation'=>$d['recommendation']??null,'status'=>$d['submit']?'submitted':'in_progress','started_at'=>$row->started_at??now(),'submitted_at'=>$d['submit']?now():null,'updated_at'=>now()]);
  if($d['submit']&&AdjudicationReadiness::ready($row->idea_id))foreach(DB::table('review_assignments')->where('idea_id',$row->idea_id)->where('kind','adjudication')->where('active',true)->pluck('user_id') as $userId)PortalNotifications::adjudicationAssigned($userId,Idea::findOrFail($row->idea_id));
  return response()->json(['status'=>$d['submit']?'submitted':'in_progress']);
 }
}
