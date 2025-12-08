@extends('layouts.app')

@section('title', 'Role Management')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Role Management</h1>
        <a href="{{ route('dashboard') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm flex items-center gap-2 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            Home
        </a>
    </div>

    <!-- Add New Role Form -->
    <div class="mb-8 bg-gray-50 p-4 rounded-lg">
        <h2 class="text-xl font-semibold mb-4">Add New Role</h2>
        <form method="POST" action="{{ route('roles.store') }}" class="flex items-end gap-4">
            @csrf
            <div class="flex-1">
                <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Role Name</label>
                <input type="text" name="name" id="name" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       placeholder="Enter role name">
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add Role
            </button>
        </form>
    </div>

    <!-- Roles List -->
    <div>
        <h2 class="text-xl font-semibold mb-4">Existing Roles</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 border-b">Role Number</th>
                        <th class="px-4 py-2 border-b">Name</th>
                        <th class="px-4 py-2 border-b">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr id="role-row-{{ $role->id }}">
                            <td class="px-4 py-2 border-b">Role_{{ $role->number }}</td>
                            <td class="px-4 py-2 border-b">
                                <span class="role-name-display-{{ $role->id }}">{{ $role->name }}</span>
                                <div class="role-name-edit-{{ $role->id }}" style="display: none;">
                                    <form method="POST" action="{{ route('roles.update', $role) }}" id="edit-form-{{ $role->id }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ $role->name }}" required
                                               class="shadow appearance-none border rounded py-1 px-2 text-gray-700 w-full">
                                    </form>
                                </div>
                            </td>
                            <td class="px-4 py-2 border-b">
                                <div class="role-actions-view-{{ $role->id }} flex gap-2">
                                    @php
                                        $lastRole = $roles->sortByDesc('number')->first();
                                        $canDelete = $role->number !== 1 && $role->id === $lastRole->id;
                                    @endphp
                                    @if($role->number !== 1)
                                        <button type="button" onclick="editRole({{ $role->id }})" 
                                                class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                            Edit
                                        </button>
                                    @endif
                                    @if($role->number === 1)
                                        <button type="button" disabled 
                                                class="text-gray-400 text-sm font-medium cursor-not-allowed">
                                            Delete
                                        </button>
                                    @elseif($canDelete)
                                        <form method="POST" action="{{ route('roles.destroy', $role) }}" class="inline"
                                              onsubmit="return confirm('Are you sure you want to delete this role? This will remove it from all users.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                                Delete
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" disabled 
                                                class="text-gray-400 text-sm font-medium cursor-not-allowed"
                                                title="Can only delete the last role">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                                <div class="role-actions-edit-{{ $role->id }} flex gap-2" style="display: none;">
                                    <button type="button" onclick="saveRole({{ $role->id }})" 
                                            class="text-green-500 hover:text-green-700 text-sm font-medium">
                                        Save
                                    </button>
                                    <button type="button" onclick="cancelEditRole({{ $role->id }})" 
                                            class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                                        Cancel
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-center text-gray-500">No roles found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const originalNames = {};

    // Initialize original names on page load
    document.addEventListener('DOMContentLoaded', function() {
        @foreach($roles as $role)
            @if($role->number !== 1)
                originalNames[{{ $role->id }}] = '{{ addslashes($role->name) }}';
            @endif
        @endforeach
    });

    function editRole(roleId) {
        // Store original name
        const displayElement = document.querySelector(`.role-name-display-${roleId}`);
        const inputElement = document.querySelector(`.role-name-edit-${roleId} input[name="name"]`);
        originalNames[roleId] = inputElement.value;

        // Hide display, show edit form
        displayElement.style.display = 'none';
        document.querySelector(`.role-name-edit-${roleId}`).style.display = 'block';

        // Hide view actions, show edit actions
        document.querySelector(`.role-actions-view-${roleId}`).style.display = 'none';
        document.querySelector(`.role-actions-edit-${roleId}`).style.display = 'flex';

        // Focus on input
        inputElement.focus();
    }

    function cancelEditRole(roleId) {
        // Restore original name
        const inputElement = document.querySelector(`.role-name-edit-${roleId} input[name="name"]`);
        inputElement.value = originalNames[roleId];

        // Show display, hide edit form
        document.querySelector(`.role-name-display-${roleId}`).style.display = 'inline';
        document.querySelector(`.role-name-edit-${roleId}`).style.display = 'none';

        // Show view actions, hide edit actions
        document.querySelector(`.role-actions-view-${roleId}`).style.display = 'flex';
        document.querySelector(`.role-actions-edit-${roleId}`).style.display = 'none';
    }

    function saveRole(roleId) {
        const form = document.getElementById(`edit-form-${roleId}`);
        form.submit();
    }
</script>
@endsection
