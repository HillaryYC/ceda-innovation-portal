<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalNotificationController extends Controller {
 public function index(Request $r){$query=DB::table('portal_notifications')->where('user_id',$r->user()->id)->where(function($q){$q->where('kind','!=','adjudication_assigned')->orWhere(function($ready){$ready->whereExists(fn($e)=>$e->selectRaw('1')->from('review_assignments')->whereColumn('review_assignments.idea_id','portal_notifications.idea_id')->where('kind','evaluation')->where('active',true))->whereNotExists(fn($e)=>$e->selectRaw('1')->from('review_assignments')->whereColumn('review_assignments.idea_id','portal_notifications.idea_id')->where('kind','evaluation')->where('active',true)->where('status','!=','submitted'));});});return ['unread'=>(clone $query)->whereNull('read_at')->count(),'data'=>$query->orderByDesc('id')->limit(30)->get(['id','idea_id','kind','title','message','read_at','created_at'])];}
 public function read(Request $r,int $notification){$updated=DB::table('portal_notifications')->where('id',$notification)->where('user_id',$r->user()->id)->update(['read_at'=>now(),'updated_at'=>now()]);abort_unless($updated,404);return ['message'=>'Notification read.'];}
}
