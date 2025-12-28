@extends('layouts.app')

@section('title', 'IP Monitor')

@section('content')
<div class="flex h-screen bg-gray-900">
    @include('admin.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
        @include('admin.partials.topbar')

        <main class="flex-1 overflow-y-auto p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-white">IP Monitor</h1>
                    <p class="text-gray-400">Monitoring IP Static Pelanggan</p>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-3">
                    <button onclick="pingAll()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-sync-alt mr-2"></i>Ping All
                    </button>
                    <form action="{{ route('admin.ip-monitor.import-customers') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-download mr-2"></i>Import dari Customer
                        </button>
                    </form>
                    <a href="{{ route('admin.ip-monitor.create') }}" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i>Tambah IP
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Total Monitor</p>
                            <p class="text-2xl font-bold text-white">{{ $stats['total'] }}</p>
                        </div>
                        <div class="h-12 w-12 bg-blue-600/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-network-wired text-blue-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Active</p>
                            <p class="text-2xl font-bold text-white">{{ $stats['active'] }}</p>
                        </div>
                        <div class="h-12 w-12 bg-cyan-600/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-cyan-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Online (UP)</p>
                            <p class="text-2xl font-bold text-green-400">{{ $stats['up'] }}</p>
                        </div>
                        <div class="h-12 w-12 bg-green-600/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-signal text-green-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Offline (DOWN)</p>
                            <p class="text-2xl font-bold text-red-400">{{ $stats['down'] }}</p>
                        </div>
                        <div class="h-12 w-12 bg-red-600/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-times-circle text-red-400 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-800 rounded-xl p-4 mb-6 border border-gray-700">
                <form method="GET" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari IP atau nama..." 
                               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-cyan-500">
                    </div>
                    <select name="status" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                        <option value="">Semua Status</option>
                        <option value="up" {{ request('status') == 'up' ? 'selected' : '' }}>UP</option>
                        <option value="down" {{ request('status') == 'down' ? 'selected' : '' }}>DOWN</option>
                        <option value="unknown" {{ request('status') == 'unknown' ? 'selected' : '' }}>Unknown</option>
                    </select>
                    <select name="active" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                        <option value="">Semua</option>
                        <option value="yes" {{ request('active') == 'yes' ? 'selected' : '' }}>Active</option>
                        <option value="no" {{ request('active') == 'no' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-2 rounded-lg transition">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </form>
            </div>

            <!-- Monitor Table -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">IP Address</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Latency</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Uptime</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Last Check</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @forelse($monitors as $monitor)
                            <tr class="hover:bg-gray-700/50" id="monitor-{{ $monitor->id }}">
                                <td class="px-4 py-3">
                                    @if($monitor->status === 'up')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900 text-green-300">
                                        <span class="w-2 h-2 bg-green-400 rounded-full mr-1.5 animate-pulse"></span>UP
                                    </span>
                                    @elseif($monitor->status === 'down')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-900 text-red-300">
                                        <span class="w-2 h-2 bg-red-400 rounded-full mr-1.5"></span>DOWN
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-700 text-gray-300">
                                        <span class="w-2 h-2 bg-gray-400 rounded-full mr-1.5"></span>Unknown
                                    </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-white font-mono">{{ $monitor->ip_address }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-300">{{ $monitor->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-300">
                                    @if($monitor->customer)
                                    <a href="{{ route('admin.customers.show', $monitor->customer) }}" class="text-cyan-400 hover:underline">
                                        {{ $monitor->customer->name }}
                                    </a>
                                    @else
                                    -
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($monitor->latency_ms)
                                    <span class="text-{{ $monitor->latency_ms < 50 ? 'green' : ($monitor->latency_ms < 100 ? 'yellow' : 'red') }}-400">
                                        {{ $monitor->latency_ms }}ms
                                    </span>
                                    @else
                                    <span class="text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-{{ $monitor->uptime_percent >= 99 ? 'green' : ($monitor->uptime_percent >= 95 ? 'yellow' : 'red') }}-400">
                                        {{ $monitor->uptime_percent }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-400 text-sm">
                                    {{ $monitor->last_check ? $monitor->last_check->diffForHumans() : 'Never' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <button onclick="pingMonitor({{ $monitor->id }})" class="text-green-400 hover:text-green-300" title="Ping">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <a href="{{ route('admin.ip-monitor.show', $monitor) }}" class="text-cyan-400 hover:text-cyan-300" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.ip-monitor.edit', $monitor) }}" class="text-yellow-400 hover:text-yellow-300" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.ip-monitor.toggle', $monitor) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-{{ $monitor->is_active ? 'gray' : 'blue' }}-400 hover:text-{{ $monitor->is_active ? 'gray' : 'blue' }}-300" title="{{ $monitor->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <i class="fas fa-{{ $monitor->is_active ? 'pause' : 'play' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.ip-monitor.destroy', $monitor) }}" method="POST" class="inline" onsubmit="return confirm('Hapus monitor ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-400 hover:text-red-300" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                    <i class="fas fa-network-wired text-4xl mb-3 opacity-50"></i>
                                    <p>Belum ada IP yang dimonitor</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($monitors->hasPages())
                <div class="px-4 py-3 border-t border-gray-700">
                    {{ $monitors->links() }}
                </div>
                @endif
            </div>

            <!-- Recent Alerts -->
            @if($recentAlerts->count() > 0)
            <div class="mt-6 bg-gray-800 rounded-xl border border-gray-700 p-4">
                <h3 class="text-lg font-semibold text-white mb-4">
                    <i class="fas fa-bell mr-2 text-yellow-400"></i>Recent Alerts
                </h3>
                <div class="space-y-2">
                    @foreach($recentAlerts as $alert)
                    <div class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <i class="fas {{ $alert->type_icon }} text-{{ $alert->type_color }}-400"></i>
                            <div>
                                <p class="text-white text-sm">{{ $alert->title }}</p>
                                <p class="text-gray-400 text-xs">{{ $alert->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @if(!$alert->is_read)
                        <form action="{{ route('admin.ip-monitor.alert.read', $alert) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-xs text-cyan-400 hover:underline">Mark as read</button>
                        </form>
                        @endif
                    </div>
                    @endforeach
                </div>
                <a href="{{ route('admin.ip-monitor.alerts') }}" class="block mt-3 text-center text-cyan-400 hover:underline text-sm">
                    Lihat semua alerts →
                </a>
            </div>
            @endif
        </main>
    </div>
</div>

<script>
function pingMonitor(id) {
    const row = document.getElementById('monitor-' + id);
    const btn = row.querySelector('.fa-sync-alt');
    btn.classList.add('fa-spin');
    
    fetch(`/admin/ip-monitor/${id}/ping`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        btn.classList.remove('fa-spin');
        if (data.success) {
            location.reload();
        }
    })
    .catch(() => {
        btn.classList.remove('fa-spin');
        alert('Gagal melakukan ping');
    });
}

function pingAll() {
    if (!confirm('Ping semua IP yang aktif?')) return;
    
    fetch('{{ route("admin.ip-monitor.ping-all") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        alert(`Berhasil ping ${data.checked} IP`);
        location.reload();
    })
    .catch(() => alert('Gagal melakukan ping'));
}
</script>
@endsection
