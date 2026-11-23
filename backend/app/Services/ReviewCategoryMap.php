<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;

class ReviewCategoryMap {
 public static function summaries():array{return DB::table('review_summary_categories')->orderBy('position')->pluck('name')->all();}
 public static function for(int $programme,string $detailed):?string{return DB::table('review_category_mappings as m')->join('review_summary_categories as s','s.id','=','m.summary_category_id')->where('m.programme_id',$programme)->where('m.detailed_category',$detailed)->value('s.name');}
 public static function all(int $programme):array{return DB::table('review_category_mappings as m')->join('review_summary_categories as s','s.id','=','m.summary_category_id')->where('m.programme_id',$programme)->pluck('s.name','m.detailed_category')->all();}
}
