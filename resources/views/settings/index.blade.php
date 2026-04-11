@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h1 class="text-2xl font-bold mb-4">Settings</h1>
        <p class="text-gray-600 text-sm mb-6">Application settings will appear here. Use the user menu for profile, password, and roles.</p>
        <div class="space-y-2 text-sm">
            <a href="{{ route('profile.show') }}" class="block text-blue-600 hover:text-blue-800 font-medium">Profile</a>
            <a href="{{ route('profile.password') }}" class="block text-blue-600 hover:text-blue-800 font-medium">Change password</a>
        </div>
    </div>
</div>
@endsection
