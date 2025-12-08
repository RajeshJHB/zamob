@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-6 text-center">Profile</h2>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" id="profile-form">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required autofocus
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror">
            @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('email') border-red-500 @enderror">
            @error('email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
            <div id="email-warning" class="hidden mt-2 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded text-sm">
                <strong>Warning:</strong> If you change your email address, you will need to verify the new email before you can login again. A verification email will be sent to your new address.
            </div>
        </div>

        <div class="mb-4">
            <label for="current_password" class="block text-gray-700 text-sm font-bold mb-2">Current Password</label>
            <input type="password" name="current_password" id="current_password" required
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('current_password') border-red-500 @enderror">
            @error('current_password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">New Password (Optional)</label>
            <input type="password" name="password" id="password"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('password') border-red-500 @enderror"
                   placeholder="Leave blank to keep current password">
            @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="password_confirmation" class="block text-gray-700 text-sm font-bold mb-2">Confirm New Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                   placeholder="Leave blank if not changing password">
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">Roles Enabled</label>
            @if($roles->count() > 0)
                <div class="shadow border rounded w-full py-2 px-3 bg-gray-50">
                    <ul class="list-disc list-inside space-y-2">
                        @foreach($roles as $role)
                            <li class="text-gray-700">
                                <strong>Role_{{ $role->number }}</strong> - {{ $role->name }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="shadow border rounded w-full py-2 px-3 bg-gray-50">
                    <p class="text-gray-600">No roles assigned.</p>
                </div>
            @endif
        </div>

        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Update Profile
            </button>
            <a href="{{ route('dashboard') }}" class="text-sm text-blue-500 hover:text-blue-800">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const originalEmail = '{{ $user->email }}';
    const emailWarning = document.getElementById('email-warning');
    const profileForm = document.getElementById('profile-form');

    // Show warning if email changes
    emailInput.addEventListener('input', function() {
        if (this.value !== originalEmail) {
            emailWarning.classList.remove('hidden');
        } else {
            emailWarning.classList.add('hidden');
        }
    });

    // Confirm before submitting if email changed
    profileForm.addEventListener('submit', function(e) {
        if (emailInput.value !== originalEmail) {
            if (!confirm('You are changing your email address. You will need to verify the new email before you can login again. A verification email will be sent to your new address. Do you want to continue?')) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>
@endsection

