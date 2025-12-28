@extends('layouts.app')

@section('title', 'SNMP Network Monitoring')

@section('content')
<div class="flex h-screen bg-gray-900">
    @include('admin.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
        @include('admin.partials.topbar')

        <main class="flex-1 overflow-y-auto p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-white">SNMP Network Monitoring</h1>
                    <p class="text-gray-400">Monitor perangkat jaringan real-time</p>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-3">
                    <a href="{{ route('admin.snmp.dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <button onclick="document.getElementById('addDeviceModal').classList.remove('hidden')" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i>Add Device
                    </button>
                </div>
            </div>

            @if(!$enabled)
            <div class="bg-yellow-900/50 border border-yellow-600 rounded-xl p-4 mb-6">
                <div class="flex">
                    <i class="fas fa-exclamation-triangle text-yellow-400 mr-3 mt-1"></i>
                    <div>
                        <h3 class="text-yellow-300 font-medium">SNMP Tidak Aktif</h3>
                        <p class="text-yellow-400/80 text-sm mt-1">
                            Aktifkan SNMP dengan mengatur <code class="bg-yellow-900 px-1 rounded">SNMP_ENABLED=true</code> di file .env.
                            Pastikan PHP SNMP extension sudah terinstall.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Total Devices</p>
                            <p class="text-2xl font-bold text-white">{{ $devices->count() }}</p>
                        </div>
                        <div class="h-12 w-12 bg-blue-600/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-server text-blue-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Online</p>
                            <p class="text-2xl font-bold text-green-400">{{ $devices->where('status', 'online')->count() }}</p>
                        </div>
                        <div class="h-12 w-12 bg-green-600/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Offline</p>
                            <p class="text-2xl font-bold text-red-400">{{ $devices->where('status', 'offline')->count() }}</p>
                        </div>
                        <div class="h-12 w-12 bg-red-600/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-times-circle text-red-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Unknown</p>
                            <p class="text-2xl font-bold text-gray-400">{{ $devices->where('status', 'unknown')->count() }}</p>
                        </div>
                        <div class="h-12 w-12 bg-gray-600/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-question-circle text-gray-400 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Devices Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($devices as $device)
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 hover:border-gray-600 transition">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div class="p-3 rounded-lg bg-{{ $device->status_color }}-900/50">
                                <i class="fas {{ $device->type_icon }} text-{{ $device->status_color }}-400 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="font-bold text-white">{{ $device->name }}</h3>
                                <p class="text-sm text-gray-400 font-mono">{{ $device->host }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ ucfirst($device->type) }}</p>
                            </div>
                        </div>
                        <span class="status-indicator w-3 h-3 rounded-full bg-{{ $device->status_color }}-400 {{ $device->status === 'online' ? 'animate-pulse' : '' }}" data-host="{{ $device->host }}"></span>
                    </div>
                    
                    @if($device->location)
                    <p class="text-gray-500 text-sm mt-3">
                        <i class="fas fa-map-marker-alt mr-1"></i>{{ $device->location }}
                    </p>
                    @endif

                    @if($device->cpu_usage || $device->memory_usage)
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        @if($device->cpu_usage)
                        <div class="bg-gray-700/50 rounded-lg p-2">
                            <p class="text-xs text-gray-400">CPU</p>
                            <p class="text-sm text-white">{{ $device->cpu_usage }}%</p>
                        </div>
                        @endif
                        @if($device->memory_usage)
                        <div class="bg-gray-700/50 rounded-lg p-2">
                            <p class="text-xs text-gray-400">Memory</p>
                            <p class="text-sm text-white">{{ $device->memory_usage }}%</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    <div class="mt-4 flex space-x-2">
                        <a href="{{ route('admin.snmp.device', $device->host) }}" class="flex-1 text-center py-2 bg-cyan-900/50 text-cyan-400 rounded-lg hover:bg-cyan-900 text-sm transition">
                            <i class="fas fa-eye mr-1"></i>Detail
                        </a>
                        <form action="{{ route('admin.snmp.devices.delete', $device->id) }}" method="POST" class="flex-1">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-full py-2 bg-red-900/50 text-red-400 rounded-lg hover:bg-red-900 text-sm transition" onclick="return confirm('Hapus device ini?')">
                                <i class="fas fa-trash mr-1"></i>Hapus
                            </button>
                        </form>
                    </div>
                    
                    @if($device->last_check)
                    <p class="text-xs text-gray-500 mt-3 text-center">
                        Last check: {{ $device->last_check->diffForHumans() }}
                    </p>
                    @endif
                </div>
                @empty
                <div class="col-span-3 bg-gray-800 rounded-xl border border-gray-700 p-8 text-center">
                    <i class="fas fa-server text-4xl text-gray-600 mb-3"></i>
                    <p class="text-gray-400">Belum ada perangkat yang dimonitor</p>
                    <button onclick="document.getElementById('addDeviceModal').classList.remove('hidden')" class="mt-4 text-cyan-400 hover:underline">
                        <i class="fas fa-plus mr-1"></i>Tambah Device
                    </button>
                </div>
                @endforelse
            </div>
        </main>
    </div>
</div>

<!-- Add Device Modal -->
<div id="addDeviceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md border border-gray-700">
        <h3 class="text-lg font-bold text-white mb-4">Add Network Device</h3>
        <form action="{{ route('admin.snmp.devices.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Device Name *</label>
                    <input type="text" name="name" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-cyan-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">IP Address *</label>
                    <input type="text" name="host" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-cyan-500" placeholder="192.168.1.1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">SNMP Community</label>
                    <input type="text" name="community" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-cyan-500" placeholder="public">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Device Type *</label>
                    <select name="type" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-cyan-500">
                        <option value="router">Router</option>
                        <option value="switch">Switch</option>
                        <option value="olt">OLT</option>
                        <option value="server">Server</option>
                        <option value="ap">Access Point</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Location</label>
                    <input type="text" name="location" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-cyan-500" placeholder="Data Center 1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-cyan-500"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition">
                    <i class="fas fa-plus mr-2"></i>Add Device
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh status every 30 seconds
    setInterval(function() {
        document.querySelectorAll('.status-indicator').forEach(function(el) {
            const host = el.dataset.host;
            fetch(`{{ route('admin.snmp.ping') }}?host=${host}`)
                .then(r => r.json())
                .then(data => {
                    el.classList.remove('bg-gray-400', 'bg-green-400', 'bg-red-400');
                    el.classList.add(data.online ? 'bg-green-400' : 'bg-red-400');
                    if (data.online) {
                        el.classList.add('animate-pulse');
                    } else {
                        el.classList.remove('animate-pulse');
                    }
                });
        });
    }, 30000);
});
</script>
@endpush
@endsection
