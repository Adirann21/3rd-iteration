@extends('layouts.app')

@section('title', 'Verify OTP - Decrypt Utility')

@section('content')
<!-- OTP verification modal for decrypt utility - similar to login 2FA flow -->
<main class="flex min-h-[calc(100vh-73px)]">
    <div class="w-full md:w-1/2 flex flex-col items-center justify-center px-8 py-12">
        <div class="w-full max-w-md">
            <div class="flex justify-center mb-6">
                <div class="flex items-center border border-black px-2 py-1">
                    <span class="text-xs font-medium tracking-wide">CAMPUS</span>
                    <span class="bg-black text-white text-xs font-medium px-1 ml-1">RESERVE</span>
                </div>
            </div>
            
            <h1 class="text-3xl font-bold text-center text-gray-900 mb-8">
                VERIFY YOUR ACCESS
            </h1>
            
            <div class="text-center mb-8">
                <p class="text-gray-600 mb-2">We sent a 6-digit code to your admin email.</p>
                <p class="text-sm text-gray-500">Time remaining: <span id="timer">300s</span></p>
            </div>
            
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('debug.decrypt.otp.verify') }}" class="space-y-6">
                @csrf
                
                <div class="space-y-2">
                    <label for="otp" class="block text-sm text-gray-700">Verification Code</label>
                    <input
                        id="otp"
                        name="otp"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        autocomplete="one-time-code"
                        required
                        class="w-full px-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent text-center text-lg tracking-widest"
                        placeholder="000000"
                    >
                    @error('otp')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <button
                        type="submit"
                        class="flex-1 px-8 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors"
                    >
                        Verify
                    </button>
                    <button
                        type="button"
                        onclick="document.querySelector('input[name=otp]').value = ''"
                        class="flex-1 px-8 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors"
                    >
                        Clear
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500 mt-2">
                    Didn't receive the code? Check spam folder or try again.
                </p>
                <p class="mt-4">
                    <a href="/admin/dashboard" class="text-sm text-gray-600 hover:underline">Back to Dashboard</a>
                </p>
            </div>
        </div>
    </div>
    
    <div class="hidden md:block w-1/2 relative overflow-hidden">
        <div class="absolute inset-0 bg-linear-to-br from-purple-300 via-pink-200 to-red-200"></div>
    </div>
</main>

<script>
    let timeLeft = 300; // 5 minutes
    const timerEl = document.getElementById('timer');
    
    const interval = setInterval(() => {
        timeLeft--;
        if (timerEl && timeLeft > 0) {
            timerEl.textContent = timeLeft + 's';
        } else {
            clearInterval(interval);
            if (timerEl) {
                timerEl.innerHTML = '<span class="text-red-500">Expired - Go back and request new OTP</span>';
            }
        }
    }, 1000);
</script>
@endsection
