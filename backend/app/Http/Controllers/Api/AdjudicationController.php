<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApplicationAttachment;
use App\Services\ProgrammeAccess;
use App\Services\AdjudicationReadiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdjudicationController extends Controller {
 public function index(Request $request, ProgrammeAccess $access) {
  $programmes=$access->programmes($request->user(),'reviews.adjudicate');
  abort_unless($programmes,403);
  $rows=DB::table('review_assignments as a')->join('ideas as i','i.id','=','a.idea_id')->join('review_templates as t','t.id','=','a.template_id')
   ->where('a.user_id',$request->user()->id)->where('a.kind','adjudication')->where('a.active',true)->whereIn('i.programme_id',$programmes)
   ->whereExists(fn($q)=>$q->selectRaw('1')->from('review_assignments as e')->whereColumn('e.idea_id','a.idea_id')->where('e.kind','evaluation')->where('e.active',true))
   ->whereNotExists(fn($q)=>$q->selectRaw('1')->from('review_assignments as e')->whereColumn('e.idea_id','a.idea_id')->where('e.kind','evaluation')->where('e.active',true)->where('e.status','!=','submitted'))
   ->select('a.id','a.idea_id','a.status','a.due_date','a.marks','a.review_notes','a.overall_comment','a.recommendation','a.submitted_at','i.title','i.team as applicant','i.category','i.summary','i.problem','i.solution','i.form_answers','i.form_schema','t.name as template','t.criteria')->orderByDesc('a.id')->get();
  foreach($rows as $row){
   foreach(['form_answers','form_schema','marks','review_notes','criteria'] as $field)$row->$field=$row->$field?json_decode($row->$field,true):[];
   $row->attachments=ApplicationAttachment::where('idea_id',$row->idea_id)->get();
   $row->evaluations=DB::table('review_assignments as e')->join('users as u','u.id','=','e.user_id')->join('review_templates as t','t.id','=','e.template_id')
    ->where('e.idea_id',$row->idea_id)->where('e.kind','evaluation')->where('e.active',true)->where('e.status','submitted')
    ->get(['e.id','u.name as evaluator','e.is_lead','e.marks','e.overall_comment','e.review_notes','e.recommendation','e.submitted_at','t.name as template','t.criteria'])
    ->map(function($evaluation){foreach(['marks','review_notes','criteria'] as $field)$evaluation->$field=$evaluation->$field?json_decode($evaluation->$field,true):[];return $evaluation;});
   $row->evaluator_count=DB::table('review_assignments')->where('idea_id',$row->idea_id)->where('kind','evaluation')->where('active',true)->count();
   $row->information_requests=InformationRequestController::forIdea($row->idea_id);
  }
  return response()->json(['data'=>$rows])->header('Cache-Control','no-store');
 }
 public function save(Request $request,int $assignment,ProgrammeAccess $access){
  return DB::transaction(function()use($request,$assignment,$access){
   $row=DB::table('review_assignments as a')->join('ideas as i','i.id','=','a.idea_id')->join('review_templates as t','t.id','=','a.template_id')
    ->where('a.id',$assignment)->where('a.user_id',$request->user()->id)->where('a.kind','adjudication')->where('a.active',true)->select('a.*','i.programme_id','t.criteria')->lockForUpdate()->first();
   abort_unless($row&&$access->allows($request->user(),$row->programme_id,'reviews.adjudicate'),403);
   abort_unless(AdjudicationReadiness::ready($row->idea_id),403,'Wait for all assigned evaluators to submit their assessments.');
   abort_if($row->status==='submitted',409,'This adjudication has already been submitted.');
   $data=$request->validate(['marks'=>'required|array','marks.*.score'=>'nullable|numeric|min:0','marks.*.comment'=>'nullable|string|max:5000','review_notes'=>'nullable|array|max:200','review_notes.*.criterion'=>'required|integer|min:0','review_notes.*.section'=>'required|string|max:100','review_notes.*.kind'=>'required|in:Comment,Flag','review_notes.*.quote'=>'nullable|string|max:1000','review_notes.*.text'=>'required|string|max:5000','overall_comment'=>'nullable|string|max:10000','recommendation'=>'nullable|in:fund,conditions,info,decline','submit'=>'required|boolean']);
   $criteria=json_decode($row->criteria,true)?:[];$marks=[];
   foreach($criteria as $index=>$criterion){$mark=$data['marks'][$index]??[];$score=$mark['score']??null;abort_if($score!==null&&$score>$criterion['max'],422,'A score exceeds its criterion maximum.');abort_if($data['submit']&&$score===null,422,'Assess every criterion before submitting.');$marks[]=['score'=>$score,'comment'=>trim($mark['comment']??'')];}
   foreach($data['review_notes']??[] as $note)abort_if($note['criterion']>=count($criteria),422,'A note references an invalid criterion.');
   if($data['submit']){
    abort_if(($data['recommendation']??null)==='info',422,'Send a question to the applicant instead of submitting a final decision.');
    $evaluators=DB::table('review_assignments')->where('idea_id',$row->idea_id)->where('kind','evaluation')->where('active',true);
    abort_if(!(clone $evaluators)->exists()||(clone $evaluators)->where('status','!=','submitted')->exists(),422,'Wait for all assigned evaluators to submit their assessments.');
    abort_if(trim($data['overall_comment']??'')===''||empty($data['recommendation']),422,'Add a decision rationale and outcome before submitting.');
   }
   DB::table('review_assignments')->where('id',$assignment)->update(['marks'=>json_encode($marks),'review_notes'=>json_encode($data['review_notes']??[]),'overall_comment'=>trim($data['overall_comment']??''),'recommendation'=>$data['recommendation']??null,'status'=>$data['submit']?'submitted':'in_progress','started_at'=>$row->started_at??now(),'submitted_at'=>$data['submit']?now():null,'updated_at'=>now()]);
   return ['status'=>$data['submit']?'submitted':'in_progress'];
  });
 }
}
