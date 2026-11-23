<?php
namespace App\Services;
use App\Models\Idea;
use Illuminate\Support\Facades\DB;

class PortalNotifications {
 public static function assigned(int $user,Idea $idea):void{self::create($user,$idea,'review_assigned','New application assigned','IDEA-'.$idea->id.' · '.$idea->title.' ('.$idea->category.') is ready for review.');}
 public static function adjudicationAssigned(int $user,Idea $idea):void{self::create($user,$idea,'adjudication_assigned','New adjudication assigned','IDEA-'.$idea->id.' · '.$idea->title.' ('.$idea->category.') is ready for adjudication.');}
 public static function admins(Idea $idea):void{
  $access=new ProgrammeAccess;
  foreach(DB::table('users')->where('account_status','active')->get() as $candidate){$user=\App\Models\User::find($candidate->id);if($access->allows($user,$idea->programme_id,'reviews.manage'))self::create($user->id,$idea,'allocation_needed','Application needs allocation','IDEA-'.$idea->id.' · '.$idea->title.' ('.$idea->category.') needs an evaluator.');}
 }
 private static function create(int $user,Idea $idea,string $kind,string $title,string $message):void{DB::table('portal_notifications')->updateOrInsert(['user_id'=>$user,'idea_id'=>$idea->id,'kind'=>$kind],['programme_id'=>$idea->programme_id,'title'=>$title,'message'=>$message,'read_at'=>null,'created_at'=>now(),'updated_at'=>now()]);}
}
