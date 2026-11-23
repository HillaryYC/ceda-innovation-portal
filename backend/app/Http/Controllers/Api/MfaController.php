<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\{Totp,Administration as A};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class MfaController extends Controller {
 public function setup(Request $r){abort_unless(A::requiresMfa($r->user()),403,'Authenticator verification is for staff accounts.');abort_if($r->user()->mfa_confirmed_at,409,'MFA is already enrolled.');$secret=Totp::secret();$r->user()->mfa_secret=encrypt($secret);$r->user()->save();return response()->json(['secret'=>$secret,'issuer'=>'CEDA Innovation Portal','account'=>$r->user()->email])->header('Cache-Control','no-store');}
 public function verify(Request $r){abort_unless(A::requiresMfa($r->user()),403,'Authenticator verification is for staff accounts.');$d=$r->validate(['code'=>'required|string|regex:/^\d{6}$/','purpose'=>'nullable|in:password_reset']);DB::transaction(function()use($r,$d){$u=User::lockForUpdate()->findOrFail($r->user()->id);$step=$u->mfa_secret?Totp::step(decrypt($u->mfa_secret),$d['code']):null;if($step===null||($u->mfa_last_step!==null&&$step<=$u->mfa_last_step))throw ValidationException::withMessages(['code'=>'Invalid or already used code. Wait for a new code and try again.']);$u->mfa_confirmed_at=$u->mfa_confirmed_at??now();$u->mfa_last_step=$step;$u->save();$r->session()->put('mfa_user_id',$u->id);$r->session()->put('mfa_verified_at',time());if(($d['purpose']??null)==='password_reset')$r->session()->put('password_reset_confirmed_at',time());A::audit($u,'mfa.verified','user',$u->id);});return ['message'=>'MFA verified.'];}
}
