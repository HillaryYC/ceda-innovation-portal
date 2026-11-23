<?php
namespace App\Http\Middleware;
use App\Services\Administration;
class ActiveAccount {public function handle($r,\Closure $next){abort_unless($r->user()&&Administration::active($r->user()),403,'Your account is inactive or access has expired.');if(!$r->is('api/v1/me','api/v1/logout','api/v1/mfa/*')){ if(Administration::requiresMfa($r->user()))abort_unless($r->session()->get('mfa_user_id')===$r->user()->id,403,'Verify multi-factor authentication before accessing this workspace.');}return $next($r);}}
