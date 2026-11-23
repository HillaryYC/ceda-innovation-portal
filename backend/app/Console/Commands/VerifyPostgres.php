<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB,Artisan};
use App\Models\{User,Idea};
use App\Services\{Administration,ProgrammeAccess};
use App\Http\Controllers\Api\{PeopleController,ReviewController};
use Illuminate\Http\Request;
class VerifyPostgres extends Command {
 protected $signature='ceda:verify-postgres';
 protected $description='Verify migrations, account controls and allocations in an isolated temporary PostgreSQL schema';
 public function handle():int{
  if(DB::connection()->getDriverName()!=='pgsql'){$this->error('PostgreSQL required.');return 1;}
  config(['mail.default'=>'array']);$schema='ceda_verify_'.bin2hex(random_bytes(6));$original=config('database.connections.pgsql.search_path');$conn=DB::connection();$conn->statement('CREATE SCHEMA "'.$schema.'"');
  try{
   config(['database.connections.pgsql.search_path'=>$schema]);DB::purge('pgsql');Artisan::call('migrate',['--force'=>true]);$pid=DB::table('programmes')->value('id');
   $admin=new User(['name'=>'Integration administrator','email'=>'admin@integration.test','password'=>'Isolated-testing-only-123']);$admin->account_status='active';$admin->is_master=true;$admin->save();$admin=$admin->fresh();$access=new ProgrammeAccess;
   $request=fn(array $data)=>(new Request($data))->setUserResolver(fn()=>$admin);
   $people=new PeopleController;$reviewers=[];
   foreach(['Evaluator','Adjudicator'] as $n=>$role){$r=$people->store($request(['name'=>$role,'email'=>'r'.$n.'@integration.test','type'=>'external','programme_id'=>$pid,'role_ids'=>[DB::table('roles')->where('name',$role)->value('id')],'mode'=>'create']),$access)->getData(true);$u=User::find($r['data']['id']);$u->account_status='active';$u->save();$reviewers[]=$u;}
   $idea=Idea::create(['title'=>'Isolated test application','team'=>'Test applicant','initial'=>'T','category'=>'Service','location'=>'Gaborone','color'=>'#fff','ink'=>'#000','submitted_at'=>now(),'summary'=>'Summary','problem'=>'Problem','solution'=>'Solution','status'=>'ready','complete'=>100,'checks'=>[],'programme_id'=>$pid]);
   $templates=[];foreach(['evaluation','adjudication'] as $kind)$templates[]=DB::table('review_templates')->insertGetId(['programme_id'=>$pid,'name'=>'Integration criteria','kind'=>$kind,'version'=>1,'criteria'=>'[{"name":"Innovation","max":10}]','active'=>true,'created_at'=>now(),'updated_at'=>now()]);
   $role=DB::table('roles')->where('name','Programme Administrator')->value('id');DB::table('programme_role_user')->insert(['programme_id'=>$pid,'role_id'=>$role,'user_id'=>$admin->id]);
   (new ReviewController)->allocate($request(['evaluator_ids'=>[$reviewers[0]->id],'adjudicator_id'=>$reviewers[1]->id,'lead_id'=>$reviewers[0]->id,'template_id'=>$templates[0],'adjudication_template_id'=>$templates[1],'due_date'=>now()->addDay()->format('Y-m-d')]),$idea,$access);
   if(DB::table('review_assignments')->where('active',true)->count()!==2||$idea->fresh()->status!=='assigned')throw new \RuntimeException('Allocation persistence failed.');
   DB::table('access_overrides')->insert(['user_id'=>$admin->id,'programme_id'=>$pid,'permission'=>'reviews.manage','allowed'=>false]);if($access->allows($admin,$pid,'reviews.manage'))throw new \RuntimeException('Explicit revocation failed.');
   if(DB::table('audit_events')->where('action','review.allocated')->count()!==1)throw new \RuntimeException('Audit persistence failed.');
   $this->info('PASS: isolated PostgreSQL migrations, invitations, role scopes, reviewer allocations, explicit revocation and audit persistence.');return 0;
  }catch(\Throwable $e){$this->error(get_class($e).': '.$e->getMessage());return 1;}
  finally{config(['database.connections.pgsql.search_path'=>$original]);DB::purge('pgsql');DB::connection()->statement('DROP SCHEMA "'.$schema.'" CASCADE');$this->info('Temporary verification schema removed.');}
 }
}
