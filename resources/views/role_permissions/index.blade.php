@extends('layouts.app')
@section('title', 'Role Permissions')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-1 flex items-center gap-2">
    <i class="fa-solid fa-user-shield text-slate-400"></i> Role Permissions
</h1>
<p class="text-sm text-slate-500 mb-4">
    Tick a box to grant that role the permission. <strong>Admin</strong> isn't shown here —
    it always has full access regardless of these settings.
</p>

@if(session('status'))
<div data-flash class="mb-4 rounded-md bg-emerald-50 text-emerald-700 px-4 py-2 text-sm">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('role_permissions.update') }}">
    @csrf
    @method('PUT')

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="flex border-b border-slate-200 overflow-x-auto">
            @foreach ($permissionGroups as $category => $permissions)
            <button type="button"
                    data-tabgroup="roleperms" data-tab="{{ str($category)->slug() }}"
                    onclick="switchTab('roleperms', '{{ str($category)->slug() }}')"
                    class="px-5 py-3 text-sm font-medium border-b-2 whitespace-nowrap {{ $loop->first ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500' }} hover:text-indigo-600">
                {{ $category }}
            </button>
            @endforeach
        </div>

        @foreach ($permissionGroups as $category => $permissions)
        <div data-tabpanel="roleperms" data-tab="{{ str($category)->slug() }}" class="{{ $loop->first ? '' : 'hidden' }}">
            <table class="min-w-full divide-y divide-slate-100">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase">Permission</th>
                        @foreach ($roles as $role)
                        <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500 uppercase">{{ ucfirst($role) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($permissions as $permission)
                    <tr>
                        <td class="px-4 py-2 text-sm text-slate-700">
                            {{ $permission->label }}
                            @if ($permission->description)
                            <div class="text-xs text-slate-400">{{ $permission->description }}</div>
                            @endif
                        </td>
                        @foreach ($roles as $role)
                        <td class="px-4 py-2 text-center">
                            <input type="checkbox"
                                   name="permissions[{{ $role }}][]"
                                   value="{{ $permission->permission_key }}"
                                   {{ in_array($permission->permission_key, $assigned[$role] ?? []) ? 'checked' : '' }}
                                   class="rounded border-slate-300 w-4 h-4">
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>

    <button type="submit" class="mt-4 bg-indigo-600 text-white px-6 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2">
        <i class="fa-solid fa-check"></i> Save Role Permissions
    </button>
</form>

@include('partials.table-pagination', ['paginator' => $roles])
@endsection
