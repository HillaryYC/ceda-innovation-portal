<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Administration;
class BootstrapMaster extends Command {
 protected $signature='ceda:bootstrap-master {email} {--name=Master Administrator}';
 protected $description='Provision the single Master Administrator and print a private one-time activation URL';
 public function handle():int {if(!filter_var($this->argument('email'),FILTER_VALIDATE_EMAIL)){$this->error('Invalid email');return 1;}try{$url=DB::transaction(function(){if(DB::table('users')->where('is_master',true)->lockForUpdate()->exists())throw new \RuntimeException('A Master Administrator already exists.');$u=new User(['name'=>$this->option('name'),'email'=>strtolower($this->argument('email')),'password'=>Str::random(60)]);$u->is_master=true;$u->account_status='invited';$u->save();$role=DB::table('roles')->where('name','Programme Administrator')->value('id');foreach(DB::table('programmes')->pluck('id') as $pid)DB::table('programme_role_user')->insert(['user_id'=>$u->id,'role_id'=>$role,'programme_id'=>$pid]);$token=Str::random(64);DB::table('staff_invitations')->insert(['user_id'=>$u->id,'token_hash'=>hash('sha256',$token),'expires_at'=>now()->addDays(7),'created_at'=>now(),'updated_at'=>now()]);Administration::audit(null,'master.provisioned','user',$u->id);return url('/activate?token='.$token);});$this->line($url);return 0;}catch(\Throwable $e){$this->error($e->getMessage());return 1;}}
}
