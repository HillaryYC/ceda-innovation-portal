<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Idea extends Model {
 protected $guarded=['id'];
 protected function casts():array{return ['checks'=>'array','submitted_at'=>'date','form_answers'=>'array','form_schema'=>'array','declaration_at'=>'datetime','public_published_at'=>'datetime'];}
 public function reviewAssignments(){return $this->hasMany(\App\Models\ReviewAssignment::class);}
 public function attachments(){return $this->hasMany(ApplicationAttachment::class);}
 public function evaluators(){return $this->belongsToMany(Evaluator::class);}
 public function events(){return $this->hasMany(AssessmentEvent::class)->latest('id');}
 public const STATUSES=['pending'=>'Pending assessment','information'=>'Needs information','ready'=>'Ready to assign','assigned'=>'Assigned','ineligible'=>'Ineligible'];
}
