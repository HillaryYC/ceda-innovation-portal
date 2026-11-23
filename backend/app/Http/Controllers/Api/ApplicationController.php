<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Idea;
use App\Services\ProgrammeAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
class ApplicationController extends Controller {
 public function index(Request $r,ProgrammeAccess $access){
  $data=$r->validate(['stage'=>['nullable',Rule::in(['all','ready','information','assigned','complete'])],'programme_id'=>'nullable|integer|exists:programmes,id','search'=>'nullable|string|max:150','category'=>'nullable|string|max:100','sort'=>['nullable',Rule::in(['newest','oldest'])],'page'=>'nullable|integer|min:1','per_page'=>'nullable|integer|min:1|max:10']);
  $ids=$access->programmes($r->user(),'applications.view');abort_if(!$ids,403);
  if(isset($data['programme_id']))abort_unless(in_array($data['programme_id'],$ids),403);
  $q=Idea::with('attachments')->whereIn('programme_id',$ids)->where('status','!=','draft');
  if(isset($data['programme_id']))$q->where('programme_id',$data['programme_id']);
  if(isset($data['category']))$q->where('category',$data['category']);
  if(!empty($data['search'])){$term=$data['search'];$q->where(fn($b)=>$b->where('title','like',"%$term%")->orWhere('team','like',"%$term%"));}
  $stage=$data['stage']??'all';
  if($stage==='ready')$q->where(fn($b)=>$b->where('status','ready')->orWhere(fn($c)=>$c->where('status','pending')->where('complete',100)));
  elseif($stage==='information')$q->where(fn($b)=>$b->where('status','information')->orWhere(fn($c)=>$c->where('status','pending')->where('complete','<',100)));
  elseif($stage==='complete')$q->whereHas('reviewAssignments',fn($b)=>$b->where('kind','evaluation')->where('status','submitted')->where('active',true))->whereHas('reviewAssignments',fn($b)=>$b->where('kind','adjudication')->where('status','submitted')->where('active',true));
  elseif($stage==='assigned')$q->where('status','assigned')->where(fn($b)=>$b->whereDoesntHave('reviewAssignments',fn($a)=>$a->where('kind','evaluation')->where('status','submitted')->where('active',true))->orWhereDoesntHave('reviewAssignments',fn($a)=>$a->where('kind','adjudication')->where('status','submitted')->where('active',true)));
  elseif($stage!=='all')$q->where('status',$stage);
  return ApplicationResource::collection($q->orderBy('submitted_at',($data['sort']??'newest')==='oldest'?'asc':'desc')->orderBy('id')->paginate($data['per_page']??10)->withQueryString());
 }
 public function show(Request $r,Idea $idea,ProgrammeAccess $access){abort_unless($idea->status!=='draft'&&$idea->programme_id&&$access->allows($r->user(),$idea->programme_id,'applications.view'),404);return new ApplicationResource($idea);}
 public function publish(Request $r,Idea $idea,ProgrammeAccess $access){
  abort_unless($idea->programme_id&&$access->allows($r->user(),$idea->programme_id,'reviews.manage'),403);
  $reviewed=DB::table('review_assignments')->where('idea_id',$idea->id)->where('kind','evaluation')->where('status','submitted')->where('active',true)->exists();
  $eligible=$reviewed&&DB::table('review_assignments')->where('idea_id',$idea->id)->where('kind','adjudication')->where('status','submitted')->where('active',true)->whereIn('recommendation',['fund','conditions'])->exists();
  abort_unless($eligible,422,'Only ideas recommended for funding by a submitted adjudicator decision can be published.');
  if(!$idea->public_published_at){$idea->public_published_at=now();$idea->save();}
  return response()->json(['published_at'=>$idea->public_published_at]);
 }
 public function publicIdeas(){
  return response()->json(['data'=>Idea::whereNotNull('public_published_at')->whereHas('reviewAssignments',fn($b)=>$b->where('kind','evaluation')->where('status','submitted')->where('active',true))->whereHas('reviewAssignments',fn($b)=>$b->where('kind','adjudication')->where('status','submitted')->where('active',true)->whereIn('recommendation',['fund','conditions']))->orderByDesc('public_published_at')->limit(12)->get(['id','title','category','summary','public_published_at'])])->header('Cache-Control','public, max-age=60');
 }
 public function programmes(Request $r,ProgrammeAccess $access){return response()->json(['data'=>\Illuminate\Support\Facades\DB::table('programmes')->whereIn('id',array_unique([...$access->programmes($r->user(),'applications.view'),...$access->programmes($r->user(),'people.manage'),...$access->programmes($r->user(),'reviews.manage')]))->get(['id','name','code'])]);}
}
