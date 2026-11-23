<?php
namespace Database\Seeders;
use App\Models\{User,Idea,Evaluator};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class DatabaseSeeder extends Seeder {
public function run():void {
if(!app()->environment(['local','testing']))throw new \RuntimeException('Sample data is restricted to local and testing environments.');
$u=User::firstOrNew(['email'=>'admin@ceda.test']);$u->name='Hackathon Admin';$u->role='admin';if(!$u->exists)$u->password=Hash::make(Str::random(40));$u->save();
foreach([['Naledi Dube','Business development','ND'],['Kabelo Mokoena','Technology & innovation','KM'],['Amantle Kgosietsile','Sustainability & impact','AK']] as [$name,$specialty,$initials])Evaluator::firstOrCreate(compact('name'),compact('specialty','initials'));
foreach(json_decode(file_get_contents(__DIR__.'/data/ideas.json'),true) as $row){$names=$row['evaluators']??[];unset($row['evaluators']);$row['submitted_at']=$row['date'];unset($row['date']);$row['information_request']=$row['request']??null;unset($row['request']);$row['programme_id']=\Illuminate\Support\Facades\DB::table('programmes')->where('code','CEDA-INNOVATION')->value('id');$id=$row['id'];unset($row['id']);$idea=Idea::firstOrCreate(['id'=>$id],$row);if($idea->wasRecentlyCreated&&$names)$idea->evaluators()->sync(Evaluator::whereIn('name',$names)->pluck('id'));}
}
}