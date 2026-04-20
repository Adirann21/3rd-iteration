@extends('layouts.app')

@section('title', 'Verify Decrypt Access - Campus Reserve')

@section('content')
<!-- OTP verification modal for decrypt utility access -->
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
                🔐 SECURE ACCESS
            </h1>
            
            <div class="text-center mb-8">
                <p class="text-gray-600 mb-2">You are accessing the admin decrypt utility.</p>
                <p class="text-sm text-gray-500">A verification code will be sent to your registered email.</p>
            </div>
            
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('debug.decrypt.otp') }}" class="space-y-6">
                @csrf
                
                <div class="text-center">
                    <p class="text-sm text-gray-600 mb-4">Ready to proceed?</p>
                    <button
                        type="submit"
                        class="w-full px-8 py-3 bg-black text-white rounded-full hover:bg-gray-800 transition-colors font-semibold"
                    >
                        Send OTP
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <a href="/admin/dashboard" class="text-sm text-gray-600 hover:underline">Back to Dashboard</a>
            </div>
        </div>
    </div>
    
    <div class="hidden md:block w-1/2 relative overflow-hidden">
        <div class="absolute inset-0 bg-linear-to-br from-purple-300 via-pink-200 to-red-200"></div>
    </div>
</main>
@endsection
