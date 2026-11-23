<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Idea;
use App\Services\Administration;

class SeedTestIdeas extends Command
{
 protected $signature = 'ceda:seed-test-ideas';
 protected $description = 'Create ten clearly marked fictional applications for production testing';
 public function handle(): int
 {
  $programme = DB::table('programmes')->orderBy('id')->first();
  if (!$programme) { $this->error('No programme exists.'); return 1; }
  $rows = [
['HarvestLink Marketplace','Agriculture','Gaborone','Small farmers cannot reliably reach buyers before produce spoils.','A mobile marketplace connects growers to shops and restaurants, groups collection trips, and earns a small fee on completed orders.'],
['Solar Cold Rooms','Agriculture','Maun','Fresh produce loses value where affordable cold storage is unavailable.','Solar-powered shared cold rooms charge farmers per crate per day and offer scheduled collection for nearby markets.'],
['Circular Brick Works','Green innovation','Francistown','Plastic waste accumulates while small builders face high material costs.','A workshop converts sorted plastic and sand into tested paving blocks, selling to property owners and local contractors.'],
['PulaBooks','Financial inclusion','Gaborone','Informal traders struggle to track sales, expenses, and cash flow.','An offline-first bookkeeping service records daily transactions and provides simple cash forecasts through an affordable monthly subscription.'],
['SkillBridge Apprenticeships','Youth & employment','Molepolole','Young job seekers lack practical experience and access to local employers.','A placement platform pairs trainees with vetted tradespeople, tracks practical tasks, and charges employers a placement fee.'],
['Village Trails','Tourism','Kasane','Visitors struggle to discover locally owned experiences beyond major attractions.','A booking service lists community walks, food experiences, and craft workshops, paying hosts directly and retaining a booking commission.'],
['WaterWatch Sensors','Green innovation','Palapye','Small businesses discover water leaks only after receiving expensive bills.','Low-cost sensors monitor water flow and send leak alerts, supported by installation fees and a maintenance subscription.'],
['CareRoute Delivery','Health','Lobatse','Patients in outlying areas spend significant time travelling to collect routine supplies.','A delivery coordination service partners with licensed pharmacies to schedule secure collection and delivery of approved orders.'],
['FeedCycle','Agriculture','Serowe','Livestock keepers face seasonal feed shortages while food processors discard usable by-products.','A feed business collects suitable by-products, processes them under quality controls, and sells measured feed blends to local livestock keepers.'],
['AccessLearn','Youth & employment','Kanye','Learners with unreliable internet cannot consistently use online learning resources.','An offline learning kit provides curriculum-aligned lessons through local Wi-Fi, with school subscriptions funding support and content updates.']
];
  $created = 0;
  DB::transaction(function () use ($programme, $rows, &$created) {
   DB::table('programmes')->where('id', $programme->id)->lockForUpdate()->first();
   foreach ($rows as $index => $row) {
    [$title, $category, $location, $problem, $solution] = $row;
    $title = '[TEST] '.$title;
    if (Idea::where('programme_id', $programme->id)->where('title', $title)->exists()) continue;
    $idea = Idea::create([
     'programme_id'=>$programme->id, 'title'=>$title,
     'team'=>'Test Applicant '.str_pad((string)($index+1),2,'0',STR_PAD_LEFT),
     'initial'=>'', 'category'=>$category, 'location'=>$location,
     'color'=>'#fff', 'ink'=>'#000', 'submitted_at'=>now()->subDays($index),
     'summary'=>'Fictional test application. '.$solution,
     'problem'=>$problem, 'solution'=>$solution,
     'status'=>$index>=8?'information':'ready', 'complete'=>$index>=8?75:100,
     'checks'=>[], 'notes'=>'Seeded fictional application for portal testing.',
    ]);
    Administration::audit(null,'test_application.seeded','idea',$idea->id,['fixture'=>'production-testing-10'],$programme->id);
    $created++;
   }
  });
  $this->info('Created '.$created.' test applications in '.$programme->name.'.');
  foreach (Idea::where('programme_id',$programme->id)->whereIn('title',array_map(fn($r)=>'[TEST] '.$r[0],$rows))->get(['id','title','status']) as $idea) $this->line('IDEA-'.$idea->id.' | '.$idea->title.' | '.$idea->status);
  return 0;
 }
}
