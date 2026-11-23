<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Evaluator extends Model {protected $guarded=['id'];public function ideas(){return $this->belongsToMany(Idea::class);}}