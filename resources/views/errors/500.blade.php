@extends('layouts.app')

@section('title', 'Server Error')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-gray-800 mb-4">500</h1>
        <h2 class="text-2xl font-semibold text-gray-600 mb-4">Internal Server Error</h2>
        <p class="text-gray-500 mb-8">An unexpected error occurred. Our team has been notified.</p>
        <a href="/" class="inline-block px-6 py-3 bg-black text-white font-semibold rounded hover:bg-gray-800">
            Go Home
        </a>
    </div>
</div>
@endsection
