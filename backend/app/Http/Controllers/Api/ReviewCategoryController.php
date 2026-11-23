<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\{Administration,AutoAllocator,FormCatalog,ProgrammeAccess,ReviewCategoryMap};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReviewCategoryController extends Controller {
 public function index(Request $r,ProgrammeAccess $access){$ids=$access->programmes($r->user(),'reviews.manage');abort_unless($ids,403);return ['summaries'=>ReviewCategoryMap::summaries(),'programmes'=>collect($ids)->map(fn($id)=>['id'=>$id,'name'=>DB::table('programmes')->where('id',$id)->value('name'),'categories'=>array_keys(FormCatalog::current($id)['categories']),'mappings'=>ReviewCategoryMap::all($id)])];}
 public function save(Request $r,int $programme,ProgrammeAccess $access){abort_unless($access->allows($r->user(),$programme,'reviews.manage'),403);abort_unless(\App\Services\Administration::freshVerification($r->user(),$r->session()),423,'Verify your authenticator to update category routing.');$summaries=ReviewCategoryMap::summaries();$d=$r->validate(['detailed_category'=>['required','string',Rule::in(array_keys(FormCatalog::current($programme)['categories']))],'summary_category'=>['required','string',Rule::in($summaries)]]);DB::transaction(function()use($r,$programme,$d){$summaryId=DB::table('review_summary_categories')->where('name',$d['summary_category'])->value('id');DB::table('review_category_mappings')->updateOrInsert(['programme_id'=>$programme,'detailed_category'=>$d['detailed_category']],['summary_category_id'=>$summaryId,'created_at'=>now(),'updated_at'=>now()]);Administration::audit($r->user(),'review.category_mapped','programme',$programme,$d,$programme);});(new AutoAllocator)->assignReady($programme);return ['message'=>'Category routing saved.'];}
}
