<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ceda:allocate-ready',function(){foreach(\App\Models\Idea::where('status','ready')->where('complete',100)->limit(250)->pluck('id') as $id)(new \App\Services\AutoAllocator)->assign($id);$this->info('Ready application queue checked.');});
\Illuminate\Support\Facades\Schedule::command('ceda:allocate-ready')->everyMinute()->withoutOverlapping();

\Illuminate\Support\Facades\Schedule::call(function(){\Illuminate\Support\Facades\DB::table('operation_receipts')->where('created_at','<',now()->subDay())->delete();\Illuminate\Support\Facades\DB::table('account_challenges')->where('expires_at','<',now()->subDay())->delete();})->daily();
