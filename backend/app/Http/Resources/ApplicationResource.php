<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class ApplicationResource extends JsonResource {
 public function toArray($request):array {
  $reviewed=$this->reviewAssignments()->where('kind','evaluation')->where('status','submitted')->where('active',true)->exists();
  $decision=$reviewed?$this->reviewAssignments()->where('kind','adjudication')->where('status','submitted')->where('active',true)->latest('submitted_at')->first():null;
  $stage=$decision?'complete':($this->status==='pending'?($this->complete===100?'ready':'information'):$this->status);
  return ['id'=>$this->id,'allocation_wait_reason'=>$this->allocation_wait_reason,'reference'=>'IDEA-'.$this->id,'programme_id'=>$this->programme_id,'title'=>$this->title,'applicant_name'=>$this->team,'location'=>$this->location,'category'=>$this->category,'submitted_at'=>$this->submitted_at?->format('Y-m-d'),'stage'=>$stage,'can_publish'=>$decision&&in_array($decision->recommendation,['fund','conditions'],true),'public_published_at'=>$this->public_published_at?->toIso8601String(),'completeness'=>['result'=>$this->complete===100?'complete':'missing_information','source'=>$this->form_version?'application_form':'legacy_sample'],'summary'=>$this->summary,'problem'=>$this->problem,'solution'=>$this->solution,'form_schema'=>$this->form_schema,'form_answers'=>$this->form_answers,'attachments'=>$this->attachments];
 }
}
