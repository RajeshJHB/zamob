@extends('layouts.app')

@section('title', 'Edit User Roles')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="mb-6">
            <a href="{{ route('user-roles.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Back to Assign Roles</a>
            <h1 class="text-3xl font-bold mt-2">Edit roles</h1>
            <p class="text-sm text-gray-600 mt-2">{{ $user->name }} &middot; {{ $user->email }}</p>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('user-roles.update', $user) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <fieldset class="space-y-3">
                <legend class="text-sm font-semibold text-gray-700 mb-2">Roles</legend>
                @php
                    $userRoleIds = $user->roles->pluck('id')->all();
                @endphp
                @foreach($roles as $role)
                    @php
                        $isChecked = in_array($role->id, $userRoleIds, true);
                    @endphp
                    <label class="flex items-center gap-3 rounded border border-gray-200 px-4 py-3">
                        <input
                            type="checkbox"
                            name="roles[]"
                            value="{{ $role->id }}"
                            @checked($isChecked)
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        >
                        <span class="text-sm text-gray-800">
                            <span class="font-medium">{{ $role->name }}</span>
                            <span class="text-gray-500">(Role {{ $role->number }})</span>
                        </span>
                    </label>
                @endforeach
            </fieldset>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Save roles
                </button>
                <a href="{{ route('user-roles.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
