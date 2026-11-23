<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PublishSmartForm extends Command {
 protected $signature='ceda:publish-smart-form {--programme= : Publish only for this programme ID}';
 protected $description='Publish the versioned hackathon industry application form';

 public function handle():int {
  $definition=config('smart_application_form');
  $ids=$this->option('programme')?[(int)$this->option('programme')]:DB::table('programmes')->pluck('id')->all();
  foreach($ids as $id){
   DB::transaction(function()use($id,$definition){
    $programme=DB::table('programmes')->where('id',$id)->lockForUpdate()->first();
    if(!$programme)throw new \RuntimeException("Programme {$id} does not exist.");
    $latest=DB::table('form_definitions')->where('programme_id',$id)->orderByDesc('revision')->first();
    if($latest){
     $published=json_decode($latest->definition,true);
     $candidate=$definition;
     unset($published['version'],$candidate['version']);
     if($published===$candidate){$this->line("Programme {$id}: already published.");return;}
    }
    $revision=($latest->revision??0)+1;
    $publisher=DB::table('users')->where('is_master',true)->value('id')??DB::table('users')->value('id');
    if(!$publisher)throw new \RuntimeException('A portal administrator must exist before publishing forms.');
    DB::table('form_definitions')->insert(['programme_id'=>$id,'revision'=>$revision,'definition'=>json_encode([...$definition,'version'=>'programme-'.$id.'-v'.$revision]),'published_by'=>$publisher,'created_at'=>now(),'updated_at'=>now()]);
    $this->info("Programme {$id}: published revision {$revision}.");
   });
  }
  return self::SUCCESS;
 }
}
