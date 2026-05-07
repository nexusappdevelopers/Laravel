@extends('layouts.app')

@section('title', 'Error')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center">
                <img class="h-12 w-12" src="https://laravel.com/img/logomark.min.svg" alt="Laravel">
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Oops! Something went wrong.
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                {{ $message ?? 'An error occurred while processing your request.' }}
            </p>
        </div>

        @if(isset($trace))
            <div class="mt-6">
                <h3 class="text-lg font-medium text-gray-900">Error Details</h3>
                <div class="mt-2 bg-gray-100 p-4 rounded-lg">
                    <pre class="text-sm text-gray-800 overflow-auto max-h-64">
{{ $trace }}
                    </pre>
                </div>
            </div>
        @endif

        <div class="mt-6">
            <a href="{{ url()->previous() }}" 
               class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-indigo-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Go Back
            </a>
        </div>
    </div>
</div>
@endsection
