<?php
use Illuminate\Support\Facades\Route;
require __DIR__.'/api-session.php';
Route::post('/api/v1/chatwoot/webhook', [\App\Http\Controllers\Api\ChatwootWebhookController::class, 'handle'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
Route::get('/{path?}', fn()=>response()->file(public_path('index.html')))->where('path','(?!api(?:/|$)|up$).*');
