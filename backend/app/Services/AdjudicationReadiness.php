<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class AdjudicationReadiness {
 public static function ready(int $ideaId):bool {
  $evaluations=DB::table('review_assignments')->where('idea_id',$ideaId)->where('kind','evaluation')->where('active',true);
  return (clone $evaluations)->exists()&&!(clone $evaluations)->where('status','!=','submitted')->exists();
 }
}
