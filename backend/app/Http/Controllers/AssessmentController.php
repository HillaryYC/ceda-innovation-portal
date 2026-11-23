<?php
namespace App\Http\Controllers;
use App\Models\{Idea,Evaluator};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule,ValidationException};
class AssessmentController extends Controller {
 public function index(Request $r){
 $r->validate(['status'=>['nullable',Rule::in(array_keys(Idea::STATUSES))],'q'=>'nullable|string|max:150','category'=>'nullable|string|max:80','sort'=>['nullable',Rule::in(['newest','oldest'])]]);
 $status=$r->input('status','pending');$q=Idea::where('status',$status);
 if($r->filled('q')){$term=$r->string('q')->toString();$q->where(fn($b)=>$b->where('title','like',"%$term%")->orWhere('team','like',"%$term%"));}
 if($r->filled('category'))$q->where('category',$r->category);
 return view('ideas.index',['ideas'=>$q->orderBy('submitted_at',$r->sort==='oldest'?'asc':'desc')->orderBy('id')->paginate(12)->withQueryString(),'status'=>$status,'counts'=>Idea::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status'),'categories'=>Idea::distinct()->orderBy('category')->pluck('category')]);
 }
 public function show(Idea $idea){return view('ideas.show',['idea'=>$idea->load(['evaluators','events.user']),'evaluators'=>Evaluator::withCount('ideas')->get()]);}
 public function update(Request $r,Idea $idea){
 $data=$r->validate(['action'=>['required',Rule::in(['save','approve','request','reject','assign','resubmit'])],'notes'=>'nullable|string|max:5000','checks'=>'nullable|array|max:4','checks.*'=>['integer',Rule::in([0,1,2,3]),'distinct'],'message'=>'nullable|string|max:3000','evaluators'=>'nullable|array|max:20','evaluators.*'=>'integer|distinct|exists:evaluators,id']);
 DB::transaction(function()use($r,$idea,$data){
 $idea=Idea::lockForUpdate()->findOrFail($idea->id);$action=$data['action'];$fail=fn($m)=>throw ValidationException::withMessages(['action'=>$m]);
 if(in_array($idea->status,['assigned','ineligible'])&&!in_array($action,['save','assign']))$fail('This decision is already final.');
 if(in_array($action,['save','approve'])){$idea->notes=$data['notes']??null;if(in_array($idea->status,['pending','information']))$idea->checks=array_map(fn($n)=>in_array($n,array_map('intval',$data['checks']??[])),range(0,3));}
 if($action==='approve'){if(!in_array($idea->status,['pending','information']))$fail('This idea is not awaiting assessment.');if(count(array_filter($idea->checks))!==4||$idea->complete<100)$fail('Complete all four checks and resolve missing information before approval.');$idea->status='ready';}
 if(in_array($action,['request','reject'])){if(mb_strlen(trim($data['message']??''))<5)$fail('Please provide at least five characters explaining the decision.');$idea->status=$action==='request'?'information':'ineligible';$idea->{$action==='request'?'information_request':'reason'}=trim($data['message']);}
 if($action==='assign'){if(!in_array($idea->status,['ready','assigned'])||count(array_filter($idea->checks))!==4)$fail('Approve the assessment before assigning evaluators.');if(empty($data['evaluators']))$fail('Select at least one evaluator.');$idea->evaluators()->sync($data['evaluators']);$idea->status='assigned';}
 if($action==='resubmit'){abort_unless(app()->environment('local')&&$idea->status==='information',403);$idea->status='pending';$idea->complete=100;$idea->checks=[false,false,false,false];$idea->information_request=null;}
 $idea->save();$idea->events()->create(['user_id'=>$r->user()->id,'action'=>$action,'detail'=>$data['message']??null]);
 });
 return redirect()->route('ideas.show',$idea)->with('success',match($data['action']){'approve'=>'Approved and ready to assign.','request'=>'Information request recorded. No email has been sent.','assign'=>'Evaluator assignments saved.','reject'=>'Idea marked ineligible.','resubmit'=>'Sample response received. Ready for reassessment.',default=>'Assessment saved.'});
 }
 public function evaluators(){return view('evaluators',['evaluators'=>Evaluator::withCount('ideas')->get()]);}
}