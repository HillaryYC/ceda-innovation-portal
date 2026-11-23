<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Idea;
use App\Services\ProgrammeAccess;
use Illuminate\Http\Request;
class ReportController extends Controller {
 public function applications(Request $r,ProgrammeAccess $access){
  $ids=$access->programmes($r->user(),'reports.view');abort_unless($ids,403);
  $rows=Idea::whereIn('programme_id',$ids)->where('status','!=','draft')->orderByDesc('submitted_at')->get(['id','title','team','category','location','submitted_at','status','complete','summary']);
  return response()->json(['data'=>$rows->map(fn($i)=>['id'=>$i->id,'title'=>$i->title,'team'=>$i->team,'category'=>$i->category,'location'=>$i->location,'date'=>$i->submitted_at?->format('Y-m-d')??'','status'=>$i->status==='pending'?($i->complete===100?'ready':'information'):$i->status,'complete'=>$i->complete,'checks'=>[],'initial'=>'','color'=>'','ink'=>'','summary'=>$i->summary,'problem'=>'','solution'=>''])])->header('Cache-Control','no-store');
 }
}
