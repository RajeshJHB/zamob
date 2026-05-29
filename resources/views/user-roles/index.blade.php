@extends('layouts.app')

@section('title', 'Assign Roles')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-3xl font-bold">Assign Roles to Users</h1>
            <button
                type="submit"
                form="user-roles-bulk-form"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            >
                Save all changes
            </button>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <p class="text-sm text-gray-600 mb-4">
            Use <strong>Edit</strong> to change one user, or tick roles in the table and choose <strong>Save all changes</strong>.
        </p>

        <form id="user-roles-bulk-form" method="POST" action="{{ route('user-roles.bulk-update') }}">
            @csrf
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Email</th>
                            @foreach($roles as $role)
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase whitespace-nowrap">
                                    {{ $role->name }}<br><span class="font-normal normal-case text-gray-500">({{ $role->number }})</span>
                                </th>
                            @endforeach
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $index => $user)
                            @php
                                $userRoleIds = $user->roles->pluck('id')->all();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 border-t border-gray-200 text-sm font-medium text-gray-900">
                                    {{ $user->name }}
                                    @if(auth()->id() === $user->id)
                                        <span class="text-xs text-gray-500">(You)</span>
                                    @endif
                                    <input type="hidden" name="user_roles[{{ $index }}][user_id]" value="{{ $user->id }}">
                                </td>
                                <td class="px-4 py-3 border-t border-gray-200 text-sm text-gray-700">{{ $user->email }}</td>
                                @foreach($roles as $role)
                                    @php
                                        $isChecked = in_array($role->id, $userRoleIds, true);
                                    @endphp
                                    <td class="px-4 py-3 border-t border-gray-200 text-center">
                                        <input
                                            type="checkbox"
                                            name="user_roles[{{ $index }}][roles][]"
                                            value="{{ $role->id }}"
                                            @checked($isChecked)
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        >
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 border-t border-gray-200 text-sm">
                                    <a
                                        href="{{ route('user-roles.edit', $user) }}"
                                        class="text-blue-600 hover:text-blue-800 font-medium"
                                    >
                                        Edit
                                    </a>
                                    @if(auth()->id() !== $user->id && ! $user->isFirstUser())
                                        <button
                                            type="button"
                                            class="delete-user-btn ml-3 bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm"
                                            data-user-id="{{ $user->id }}"
                                            data-user-name="{{ $user->name }}"
                                        >
                                            Delete
                                        </button>
                                    @elseif(auth()->id() === $user->id)
                                        <span class="ml-3 text-xs text-gray-500 italic">Cannot delete yourself</span>
                                    @else
                                        <span class="ml-3 text-xs text-gray-500 italic">Cannot delete first user</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold mb-4">Confirm Delete</h3>
        <p class="mb-6">Are you sure you want to delete <span id="delete-user-name" class="font-semibold"></span>? This action cannot be undone.</p>
        <div class="flex justify-end gap-3">
            <button type="button" id="cancel-delete" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                Cancel
            </button>
            <form method="POST" id="delete-user-form" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteModal = document.getElementById('delete-modal');

    document.querySelectorAll('.delete-user-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            const deleteFormAction = '{{ route("user-roles.destroy", ":id") }}'.replace(':id', userId);

            document.getElementById('delete-user-name').textContent = userName;
            document.getElementById('delete-user-form').action = deleteFormAction;
            deleteModal.classList.remove('hidden');
            deleteModal.classList.add('flex');
        });
    });

    document.getElementById('cancel-delete').addEventListener('click', function () {
        deleteModal.classList.add('hidden');
        deleteModal.classList.remove('flex');
    });

    deleteModal.addEventListener('click', function (event) {
        if (event.target === deleteModal) {
            deleteModal.classList.add('hidden');
            deleteModal.classList.remove('flex');
        }
    });
});
</script>
@endsection
