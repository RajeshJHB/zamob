@extends('layouts.app')

@section('title', 'User Role Assignment')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold">User Role Assignment</h1>
            @if(session('success'))
                <div id="success-message" class="bg-green-50 border border-green-200 text-green-800 px-3 py-1 rounded text-sm">
                    {{ session('success') }}
                </div>
            @else
                <div id="success-message" class="hidden"></div>
            @endif
            <button type="submit" form="user-roles-form" id="save-button" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm hidden">
                Save Changes
            </button>
        </div>
        <a href="{{ route('dashboard') }}" id="back-link" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm flex items-center gap-2 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            Home
        </a>
    </div>

    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('user-roles.bulk-update') }}" id="user-roles-form">
        @csrf

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 border-b text-left align-top">User Name</th>
                        <th class="px-4 py-2 border-b text-left align-top">Email</th>
                        <th class="px-4 py-2 border-b text-left align-top">Assign Roles</th>
                        <th class="px-4 py-2 border-b text-left align-top">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="px-4 py-2 border-b">{{ $user->name }}</td>
                            <td class="px-4 py-2 border-b">{{ $user->email }}</td>
                            <td class="px-4 py-2 border-b">
                                <div class="space-y-2">
                                    @php
                                        $isCurrentUser = $user->id === auth()->id();
                                    @endphp
                                    @foreach($roles as $role)
                                        @php
                                            $isChecked = $user->roles->contains($role->id);
                                            $isRoleManager = $role->number === 1;
                                            $isCurrentUserRoleManager = $isCurrentUser && $user->isRoleManager();
                                            $isDisabled = $isCurrentUserRoleManager && $isRoleManager;
                                        @endphp
                                        <label class="flex items-center role-checkbox {{ $isDisabled ? 'opacity-75' : '' }}" 
                                               data-user-id="{{ $user->id }}">
                                            <input type="checkbox" 
                                                   name="users[{{ $user->id }}][roles][]" 
                                                   value="{{ $role->id }}"
                                                   data-original-checked="{{ $isChecked ? 'true' : 'false' }}"
                                                   {{ $isChecked ? 'checked' : '' }}
                                                   {{ $isDisabled ? 'disabled' : '' }}
                                                   class="mr-2 role-checkbox-input w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                            <span>
                                                Role_{{ $role->number }} - {{ $role->name }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-2 border-b">
                                @if($isCurrentUser)
                                    <div class="flex flex-col gap-1">
                                        @if($user->isRoleManager())
                                            <span class="text-xs text-gray-500 italic">Role_1 - Cannot remove this Role yourself</span>
                                        @endif
                                        <span class="text-xs text-gray-500 italic">Cannot delete yourself</span>
                                    </div>
                                @else
                                    <button type="button" 
                                            class="delete-user-btn bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm"
                                            data-user-id="{{ $user->id }}"
                                            data-user-name="{{ $user->name }}">
                                        Delete User
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-center text-gray-500">No users found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
</div>

<!-- Delete User Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold mb-4">Confirm Delete</h3>
        <p class="mb-6">Are you sure you want to delete <span id="delete-user-name" class="font-semibold"></span>? This action cannot be undone.</p>
        <div class="flex justify-end gap-4">
            <button type="button" id="cancel-delete" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                Cancel
            </button>
            <form method="POST" id="delete-user-form" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                    Delete User
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let hasUnsavedChanges = false;
    const form = document.getElementById('user-roles-form');
    const saveButton = document.getElementById('save-button');
    const backLink = document.getElementById('back-link');
    const deleteModal = document.getElementById('delete-modal');
    const successMessage = document.getElementById('success-message');
    let deleteForm = null;

    // Track original state
    const originalState = {};
    const checkboxes = document.querySelectorAll('.role-checkbox-input');
    
    checkboxes.forEach(checkbox => {
        const userId = checkbox.closest('.role-checkbox').dataset.userId;
        const roleId = checkbox.value;
        const key = `${userId}_${roleId}`;
        originalState[key] = checkbox.dataset.originalChecked === 'true';
    });

    // Function to check if form has unsaved changes and update styling
    function checkForChanges() {
        let hasChanges = false;
        
        checkboxes.forEach(checkbox => {
            if (checkbox.disabled) return;
            
            const userId = checkbox.closest('.role-checkbox').dataset.userId;
            const roleId = checkbox.value;
            const key = `${userId}_${roleId}`;
            const isCurrentlyChecked = checkbox.checked;
            const wasOriginallyChecked = originalState[key];
            
            // Apply yellow styling for unsaved changes, blue for saved/original state
            if (isCurrentlyChecked !== wasOriginallyChecked) {
                hasChanges = true;
                // Yellow styling for unsaved changes
                checkbox.classList.add('unsaved-changed');
                checkbox.classList.remove('text-blue-600', 'focus:ring-blue-500');
                checkbox.classList.add('text-yellow-600', 'focus:ring-yellow-500');
            } else {
                // Blue styling for saved/original state
                checkbox.classList.remove('unsaved-changed', 'text-yellow-600', 'focus:ring-yellow-500');
                checkbox.classList.add('text-blue-600', 'focus:ring-blue-500');
            }
        });
        
        hasUnsavedChanges = hasChanges;
        
        if (hasUnsavedChanges) {
            saveButton.classList.remove('hidden');
            // Hide success message when new changes are made
            if (successMessage) {
                successMessage.classList.add('hidden');
            }
        } else {
            saveButton.classList.add('hidden');
        }
    }
    
    // Initialize styling on page load
    checkForChanges();

    // Track checkbox changes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            checkForChanges();
        });
    });

    // Warn before leaving page with unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });

    // Handle link clicks
    backLink.addEventListener('click', function(e) {
        if (hasUnsavedChanges) {
            if (!confirm('You have unsaved changes. Are you sure you want to leave?')) {
                e.preventDefault();
                return false;
            }
        }
        hasUnsavedChanges = false;
    });

    // Handle form submission - clear unsaved changes flag
    form.addEventListener('submit', function() {
        hasUnsavedChanges = false;
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';
    });

    // Handle delete user button clicks
    document.querySelectorAll('.delete-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;
            const deleteFormAction = '{{ route("user-roles.destroy", ":id") }}'.replace(':id', userId);
            
            document.getElementById('delete-user-name').textContent = userName;
            document.getElementById('delete-user-form').action = deleteFormAction;
            deleteModal.classList.remove('hidden');
            deleteModal.classList.add('flex');
        });
    });

    // Handle cancel delete
    document.getElementById('cancel-delete').addEventListener('click', function() {
        deleteModal.classList.add('hidden');
        deleteModal.classList.remove('flex');
    });

    // Close modal when clicking outside
    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            deleteModal.classList.add('hidden');
            deleteModal.classList.remove('flex');
        }
    });
});
</script>

<style>
    /* Yellow checkbox styling for unsaved changes */
    input.role-checkbox-input.unsaved-changed:checked {
        accent-color: #eab308;
        background-color: #fef08a;
    }
    
    /* Blue checkbox styling for saved/original state */
    input.role-checkbox-input:checked:not(.unsaved-changed) {
        accent-color: #2563eb;
    }
</style>
@endsection
