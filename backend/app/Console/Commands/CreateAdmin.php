<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
class CreateAdmin extends Command {
 protected $signature='ceda:create-admin';
 protected $description='Create a new CEDA administrator with a securely prompted password';
 public function handle():int{
  $data=['name'=>$this->ask('Administrator name'),'email'=>$this->ask('Email address'),'password'=>$this->secret('Password (8 to 12 characters)')];
  $v=Validator::make($data,['name'=>'required|string|max:255','email'=>'required|email|unique:users,email','password'=>'required|string|min:8|max:12']);
  if($v->fails()){foreach($v->errors()->all() as $message)$this->error($message);return self::FAILURE;}
  $user=new User($data);$user->role='admin';$user->save();$this->info('Administrator created.');return self::SUCCESS;
 }
}
