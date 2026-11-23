<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller {
 public function login(Request $r){$c=$r->validate(['email'=>'required|email','password'=>'required|string']);if(!Auth::attempt($c))return back()->withErrors(['email'=>'The sign-in details are incorrect.'])->onlyInput('email');$r->session()->regenerate();return redirect()->intended('/ideas');}
 public function demo(Request $r){abort_unless(app()->environment('local'),404);Auth::login(\App\Models\User::where('email','admin@ceda.test')->where('role','admin')->firstOrFail());$r->session()->regenerate();return redirect('/ideas');}
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/login');}
}