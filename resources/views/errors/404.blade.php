@extends('layouts.app')

@section('title', 'Page Not Found')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-gray-800 mb-4">404</h1>
        <h2 class="text-2xl font-semibold text-gray-600 mb-4">Page Not Found</h2>
        <p class="text-gray-500 mb-8">The page you're looking for doesn't exist.</p>
        <a href="/" class="inline-block px-6 py-3 bg-black text-white font-semibold rounded hover:bg-gray-800">
            Go Home
        </a>
    </div>
</div>
@endsection
