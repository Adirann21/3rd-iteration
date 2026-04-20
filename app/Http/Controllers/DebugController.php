<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class DebugController extends Controller
{
    /**
     * Show the OTP request form before decrypt utility access.
     */
    public function showDecryptForm()
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            return redirect()->route('home');
        }

        // If user has already verified OTP for this session, skip to decrypt form
        if (session('decrypt_otp_verified')) {
            return view('debug.decrypt');
        }

        return view('debug.decrypt-otp');
    }

    /**
     * Issue an OTP code for decrypt utility access verification.
     */
    public function issueDecryptOtp(Request $request)
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            return redirect()->route('home');
        }

        $user = auth()->user();
        $otp = rand(100000, 999999);

        // Store OTP in session with 10-second expiry
        session([
            'decrypt_otp' => $otp,
            'decrypt_otp_expires_at' => now()->addSeconds(10),
            'decrypt_otp_attempts' => 0,
        ]);

        // Log OTP to otp.log file for reference
        $logMessage = "[" . now()->format('Y-m-d H:i:s') . "] Admin '{$user->email}' requested decrypt OTP: {$otp} (expires in 10 seconds)\n";
        file_put_contents(storage_path('logs/otp.log'), $logMessage, FILE_APPEND);

        return view('debug.decrypt-otp-verify', ['otp_issued' => true, 'timeLeft' => 10]);
    }

    /**
     * Resend OTP code for decrypt utility access.
     */
    public function resendDecryptOtp(Request $request)
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            return redirect()->route('home');
        }

        $user = auth()->user();
        $otp = rand(100000, 999999);

        // Store new OTP in session with 10-second expiry
        session([
            'decrypt_otp' => $otp,
            'decrypt_otp_expires_at' => now()->addSeconds(10),
            'decrypt_otp_attempts' => 0,
        ]);

        // Log resent OTP to otp.log file
        $logMessage = "[" . now()->format('Y-m-d H:i:s') . "] Admin '{$user->email}' resent decrypt OTP: {$otp} (expires in 10 seconds)\n";
        file_put_contents(storage_path('logs/otp.log'), $logMessage, FILE_APPEND);

        return view('debug.decrypt-otp-verify', ['otp_issued' => true, 'timeLeft' => 10]);
    }

    /**
     * Verify the OTP code for decrypt utility access.
     */
    public function verifyDecryptOtp(Request $request)
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            return redirect()->route('home');
        }

        $request->validate(['otp' => 'required|numeric|digits:6']);

        $storedOtp = session('decrypt_otp');
        $expiresAt = session('decrypt_otp_expires_at');
        $attempts = session('decrypt_otp_attempts', 0);

        if (! $storedOtp || now()->isAfter($expiresAt)) {
            return back()->with('error', 'OTP has expired. Request a new one.');
        }

        if ($attempts >= 5) {
            session()->forget(['decrypt_otp', 'decrypt_otp_expires_at', 'decrypt_otp_attempts']);

            return back()->with('error', 'Too many attempts. Please request a new OTP.');
        }

        if ((int) $request->input('otp') !== (int) $storedOtp) {
            session(['decrypt_otp_attempts' => $attempts + 1]);

            return back()->with('error', 'Invalid OTP. Please try again.');
        }

        // OTP verified! Mark session as verified and clear OTP
        session(['decrypt_otp_verified' => true]);
        session()->forget(['decrypt_otp', 'decrypt_otp_expires_at', 'decrypt_otp_attempts']);

        return redirect()->route('debug.decrypt.form');
    }

    /**
     * Decrypt the submitted Laravel encrypted string.
     */
    public function decrypt(Request $request)
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            return redirect()->route('home');
        }

        if (! session('decrypt_otp_verified')) {
            return redirect()->route('debug.decrypt.form');
        }

        $request->validate([
            'cipher' => 'required|string',
        ]);

        try {
            $plaintext = Crypt::decryptString($request->input('cipher'));
        } catch (\Throwable $e) {
            $plaintext = null;
        }

        return view('debug.decrypt', [
            'cipher' => $request->input('cipher'),
            'plaintext' => $plaintext,
            'error' => $plaintext === null ? 'Unable to decrypt. Value may not be a valid Laravel encrypted string or APP_KEY may differ.' : null,
        ]);
    }
}
