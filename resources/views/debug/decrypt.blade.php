@extends('layouts.app')

@section('title', 'Decrypt String - Campus Reserve')

@section('content')
<main class="min-h-[calc(100vh-73px)] bg-slate-50 py-10 px-4 md:px-8 xl:px-16">
    <div class="mx-auto max-w-3xl rounded-3xl bg-white p-8 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Decrypt Laravel encrypted text</h1>
                <p class="mt-2 text-sm text-slate-600">Paste a value created with <code>Crypt::encryptString()</code> and decrypt it using the app's current encryption key.</p>
            </div>
            <a href="/admin/dashboard" class="text-sm text-gray-600 hover:underline">← Back to Admin</a>
        </div>

        <form method="POST" action="{{ route('debug.decrypt') }}" class="mt-6 space-y-6">
            @csrf

            <div>
                <label for="cipher" class="block text-sm font-medium text-slate-700">Encrypted value</label>
                <textarea id="cipher" name="cipher" rows="6" required class="mt-1 block w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-black focus:ring-black">{{ old('cipher', $cipher ?? '') }}</textarea>
                @error('cipher')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="inline-flex items-center justify-center rounded-full bg-black px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800">Decrypt</button>
        </form>

        @if(isset($plaintext))
            <div class="mt-8 rounded-3xl border border-slate-200 bg-slate-50 p-6">
                <h2 class="text-lg font-semibold text-slate-900">Decrypted value</h2>
                @if($plaintext !== null)
                    <pre class="mt-3 whitespace-pre-wrap wrap-break-word text-sm text-slate-800">{{ $plaintext }}</pre>
                @else
                    <p class="mt-3 text-sm text-rose-700">{{ $error }}</p>
                @endif
            </div>
        @endif

        <div class="mt-6 text-xs text-slate-500">
            <p><strong>🔒 Session Status:</strong> OTP verified for this session. Safe to decrypt multiple values.</p>
            <p class="mt-2"><strong>Note:</strong> <code>email_hash</code> values are SHA-256 hashes and cannot be decrypted.</p>
            <p>Only values encrypted with Laravel <code>Crypt::encryptString()</code> and the app's current <code>APP_KEY</code> can be decrypted successfully.</p>
        </div>
    </div>
</main>
@endsection
