@extends('layouts.app')

@section('title', 'Import Hotspot Users')

@section('content')
<div class="min-h-screen bg-gray-100">
    @include('admin.partials.sidebar')
    
    <div class="lg:pl-64">
        @include('admin.partials.topbar')
        
        <main class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Import Hotspot Users</h1>
                    <p class="text-gray-600">Import {{ $totalUsers }} Hotspot Users dari Mikrotik</p>
                </div>
                <a href="{{ route('admin.mikrotik.sync.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    ← Kembali
                </a>
            </div>

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-orange-600">{{ $totalUsers }}</div>
                    <div class="text-sm text-gray-500">Total Hotspot Users</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-green-600">{{ count($toImport) }}</div>
                    <div class="text-sm text-gray-500">Siap Import (Baru)</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-yellow-600">{{ count($existing) }}</div>
                    <div class="text-sm text-gray-500">Sudah Ada di Database</div>
                </div>
            </div>

            <form action="{{ route('admin.mikrotik.sync.hotspot.import') }}" method="POST">
                @csrf
                
                <!-- Options -->
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <h3 class="font-semibold text-gray-700 mb-3">Opsi Import</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Default Package</label>
                            <select name="default_package_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Pilih Default Package --</option>
                                @foreach($localPackages as $package)
                                    <option value="{{ $package->id }}">{{ $package->name }} ({{ $package->speed }})</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Digunakan jika profile tidak ter-mapping</p>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="skip_existing" value="1" checked 
                                   class="rounded border-gray-300 text-orange-600" id="skip_existing">
                            <label for="skip_existing" class="ml-2 text-sm text-gray-700">
                                Skip yang sudah ada di database
                            </label>
                        </div>
                    </div>
                </div>

                <!-- New Users Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                    <div class="p-4 bg-green-50 border-b flex justify-between items-center">
                        <h3 class="font-semibold text-green-700">
                            {{ count($toImport) }} Users Siap Import
                        </h3>
                        <div>
                            <button type="button" onclick="selectAll(true)" class="text-sm text-orange-600 hover:underline mr-3">
                                Pilih Semua
                            </button>
                            <button type="button" onclick="selectAll(false)" class="text-sm text-gray-600 hover:underline">
                                Batal Pilih
                            </button>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        <input type="checkbox" id="selectAllCheckbox" onchange="selectAll(this.checked)" 
                                               class="rounded border-gray-300 text-orange-600">
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Password</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profile</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Limit</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">MAC Address</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($toImport as $user)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" name="users[]" value="{{ $user['name'] }}" 
                                                   class="user-checkbox rounded border-gray-300 text-orange-600" checked>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                            {{ $user['name'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            <span class="password-hidden">••••••••</span>
                                            <span class="password-visible hidden">{{ $user['password'] }}</span>
                                            <button type="button" onclick="togglePassword(this)" class="ml-2 text-orange-600 text-xs">
                                                show
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded text-xs">
                                                {{ $user['profile'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            @if($user['limit_uptime'])
                                                <span class="text-xs">⏱ {{ $user['limit_uptime'] }}</span>
                                            @endif
                                            @if($user['limit_bytes_total'])
                                                <span class="text-xs ml-1">📊 {{ formatBytes($user['limit_bytes_total']) }}</span>
                                            @endif
                                            @if(!$user['limit_uptime'] && !$user['limit_bytes_total'])
                                                <span class="text-gray-400">Unlimited</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ $user['mac_address'] ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($user['disabled'])
                                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Disabled</span>
                                            @else
                                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Active</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                            Semua Hotspot Users sudah ada di database
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if(count($toImport) > 0)
                        <div class="p-4 bg-gray-50 border-t">
                            <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                                Import <span id="selectedCount">{{ count($toImport) }}</span> Users
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Existing Users Table -->
                @if(count($existing) > 0)
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-4 bg-yellow-50 border-b">
                            <h3 class="font-semibold text-yellow-700">
                                {{ count($existing) }} Users Sudah Ada di Database
                            </h3>
                        </div>
                        
                        <div class="overflow-x-auto max-h-64 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profile</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">MAC Address</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($existing as $user)
                                        <tr class="bg-yellow-50">
                                            <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                                {{ $user['name'] }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded text-xs">
                                                    {{ $user['profile'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                {{ $user['mac_address'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">
                                                    Sudah Ada
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </form>
        </main>
    </div>
</div>

<script>
function selectAll(checked) {
    document.querySelectorAll('.user-checkbox').forEach(function(checkbox) {
        checkbox.checked = checked;
    });
    document.getElementById('selectAllCheckbox').checked = checked;
    updateSelectedCount();
}

function togglePassword(btn) {
    const row = btn.closest('td');
    const hidden = row.querySelector('.password-hidden');
    const visible = row.querySelector('.password-visible');
    
    if (hidden.classList.contains('hidden')) {
        hidden.classList.remove('hidden');
        visible.classList.add('hidden');
        btn.textContent = 'show';
    } else {
        hidden.classList.add('hidden');
        visible.classList.remove('hidden');
        btn.textContent = 'hide';
    }
}

function updateSelectedCount() {
    const count = document.querySelectorAll('.user-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count;
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.user-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', updateSelectedCount);
    });
});
</script>
@endsection

@php
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
@endphp
