<?php

namespace App\Http\Controllers;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    public function AdminLogout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }


    public function AdminLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::Attempt($credentials)) {
            $user = Auth::user();

            $verificationCode = random_int(100000, 999999);
            session(['verification_code' => $verificationCode, 'user_id' => $user->id]);

            Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));
            Auth::logout();

            return redirect()->route('custom.verification.form')->with('status', 'Verification code send to your Mail');
        }
        return redirect()->back()->withErrors(['email' => 'Inavlid Credentials Provided']);
    }

    public function showVerification()
    {
        return  view('auth.verify');
    }

    public function VerificationVerify(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric'
        ]);

        if ($request->code == session('verification_code')) {
            Auth::loginUsingId(session('user_id'));

            session()->forget(['verification_code', 'user_id']);
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['code' => 'Invalid Verification Code']);
    }
}
