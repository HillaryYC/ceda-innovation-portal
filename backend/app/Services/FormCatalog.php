<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class FormCatalog {
 public static function current(int $programme):array{
  $row=DB::table('form_definitions')->where('programme_id',$programme)->orderByDesc('revision')->first();
  if($row)return json_decode($row->definition,true);
  $form=config('application_form');$form['mode']='online';$form['version']='online-v2';$stageQuestions=[['key'=>'validation_plan','label'=>'How will you test this idea?','hint'=>'Describe your first experiment, who will take part, and what would show the idea can work.','stages'=>['Idea / concept']],['key'=>'pilot_results','label'=>'What have you tested and what did you learn?','hint'=>'Explain your prototype or pilot, the results, and what you changed afterwards.','stages'=>['Prototype / pilot']],['key'=>'business_traction','label'=>'What evidence shows your business is operating?','hint'=>'Describe current sales, customers, delivery capacity and lessons learned. Do not include customer names or contact information.','stages'=>['Operating business']],['key'=>'growth_plan','label'=>'How will you grow the business?','hint'=>'Explain demand, expansion needs, capacity, and the milestones you will use to measure growth.','stages'=>['Growing business']]];foreach($form['categories'] as &$category)foreach($stageQuestions as $q)$category['questions'][]=[...$q,'required'=>true,'max'=>3000,'type'=>'textarea'];unset($category);return $form;
 }
 public static function questions(array $schema,string $category,?string $stage):array{return array_values(array_filter($schema['categories'][$category]['questions']??[],fn($q)=>empty($q['stages'])||in_array($stage,$q['stages'],true)));}
}
