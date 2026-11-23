<?php
namespace App\Jobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
class AllocateCompleteApplication implements ShouldQueue {
 use Queueable;
 public int $tries=3;
 public function __construct(public int $ideaId){}
 public function handle():void{(new \App\Services\AutoAllocator)->assign($this->ideaId);}
 public function failed(?\Throwable $error):void{\App\Models\Idea::whereKey($this->ideaId)->where('status','ready')->update(['allocation_wait_reason'=>'Automatic allocation needs attention. An administrator can assign this application manually.']);}
}
