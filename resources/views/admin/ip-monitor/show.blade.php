@extends('layouts.app')

@section('title', 'Detail IP Monitor')

@section('content')
<div class="flex h-screen bg-gray-900">
    @include('admin.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
        @include('admin.partials.topbar')

        <main class="flex-1 overflow-y-auto p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div class="flex items-center">
                    <a href="{{ route('admin.ip-monitor.index') }}" class="text-gray-400 hover:text-white mr-4">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-white flex items-center">
                            <span class="w-3 h-3 rounded-full bg-{{ $ipMonitor->status_color }}-400 mr-3 {{ $ipMonitor->status === 'up' ? 'animate-pulse' : '' }}"></span>
                            {{ $ipMonitor->ip_address }}
                        </h1>
                        <p class="text-gray-400">{{ $ipMonitor->display_name }}</p>
                    </div>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-3">
                    <button onclick="pingNow()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-sync-alt mr-2" id="ping-icon"></i>Ping Now
                    </button>
                    <a href="{{ route('admin.ip-monitor.edit', $ipMonitor) }}" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                            <p class="text-gray-400 text-sm">Status</p>
                            <p class="text-xl font-bold text-{{ $ipMonitor->status_color }}-400 uppercase">{{ $ipMonitor->status }}</p>
                        </div>
                        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                            <p class="text-gray-400 text-sm">Latency</p>
                            <p class="text-xl font-bold text-white">{{ $ipMonitor->latency_ms ?? '-' }} <span class="text-sm text-gray-400">ms</span></p>
                        </div>
                        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                            <p class="text-gray-400 text-sm">Uptime (30d)</p>
                            <p class="text-xl font-bold text-{{ $uptimeStats['uptime_percent'] >= 99 ? 'green' : ($uptimeStats['uptime_percent'] >= 95 ? 'yellow' : 'red') }}-400">
                                {{ $uptimeStats['uptime_percent'] }}%
                            </p>
                        </div>
                        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                            <p class="text-gray-400 text-sm">Avg Latency</p>
                            <p class="text-xl font-bold text-white">{{ $uptimeStats['avg_latency_ms'] ?? '-' }} <span class="text-sm text-gray-400">ms</span></p>
                        </div>
                    </div>

                    <!-- Uptime Chart -->
                    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Uptime (7 Hari Terakhir)</h3>
                        <div class="flex items-end justify-between h-32 space-x-2">
                            @foreach($dailyData as $day)
                            <div class="flex-1 flex flex-col items-center">
                                <div class="w-full bg-gray-700 rounded-t relative" style="height: {{ $day['uptime'] }}%">
                                    <div class="absolute inset-0 bg-{{ $day['uptime'] >= 99 ? 'green' : ($day['uptime'] >= 95 ? 'yellow' : 'red') }}-500 rounded-t opacity-80"></div>
                                </div>
                                <span class="text-xs text-gray-400 mt-2">{{ $day['label'] }}</span>
                                <span class="text-xs text-{{ $day['uptime'] >= 99 ? 'green' : ($day['uptime'] >= 95 ? 'yellow' : 'red') }}-400">{{ $day['uptime'] }}%</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Recent Logs -->
                    <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-700">
                            <h3 class="text-lg font-semibold text-white">Log Terbaru</h3>
                        </div>
                        <div class="overflow-x-auto max-h-96">
                            <table class="w-full">
                                <thead class="bg-gray-700/50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400">Waktu</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400">Status</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400">Latency</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-400">Packet Loss</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-700">
                                    @forelse($recentLogs as $log)
                                    <tr class="hover:bg-gray-700/50">
                                        <td class="px-4 py-2 text-gray-300 text-sm">{{ $log->checked_at->format('d/m H:i:s') }}</td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $log->status_color }}-900 text-{{ $log->status_color }}-300">
                                                {{ strtoupper($log->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-300 text-sm">{{ $log->latency_ms ?? '-' }} ms</td>
                                        <td class="px-4 py-2 text-gray-300 text-sm">{{ $log->packet_loss ?? 0 }}%</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">Belum ada log</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Info Card -->
                    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Informasi</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-gray-400 text-sm">IP Address</dt>
                                <dd class="text-white font-mono">{{ $ipMonitor->ip_address }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-sm">Nama</dt>
                                <dd class="text-white">{{ $ipMonitor->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-sm">Customer</dt>
                                <dd class="text-white">
                                    @if($ipMonitor->customer)
                                    <a href="{{ route('admin.customers.show', $ipMonitor->customer) }}" class="text-cyan-400 hover:underline">
                                        {{ $ipMonitor->customer->name }}
                                    </a>
                                    @else
                                    -
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-sm">Check Interval</dt>
                                <dd class="text-white">{{ $ipMonitor->check_interval }} detik</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-sm">Alert Threshold</dt>
                                <dd class="text-white">{{ $ipMonitor->alert_threshold }} kali gagal</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-sm">Last Check</dt>
                                <dd class="text-white">{{ $ipMonitor->last_check ? $ipMonitor->last_check->format('d/m/Y H:i:s') : 'Never' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-sm">Last Up</dt>
                                <dd class="text-green-400">{{ $ipMonitor->last_up ? $ipMonitor->last_up->format('d/m/Y H:i:s') : '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-sm">Last Down</dt>
                                <dd class="text-red-400">{{ $ipMonitor->last_down ? $ipMonitor->last_down->format('d/m/Y H:i:s') : '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-sm">Status</dt>
                                <dd>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $ipMonitor->is_active ? 'bg-green-900 text-green-300' : 'bg-gray-700 text-gray-300' }}">
                                        {{ $ipMonitor->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $ipMonitor->alert_enabled ? 'bg-yellow-900 text-yellow-300' : 'bg-gray-700 text-gray-300' }} ml-1">
                                        Alert {{ $ipMonitor->alert_enabled ? 'ON' : 'OFF' }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Recent Alerts -->
                    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Alerts Terbaru</h3>
                        @if($ipMonitor->alerts->count() > 0)
                        <div class="space-y-3">
                            @foreach($ipMonitor->alerts as $alert)
                            <div class="p-3 bg-gray-700/50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <i class="fas {{ $alert->type_icon }} text-{{ $alert->type_color }}-400"></i>
                                    <span class="text-white text-sm">{{ $alert->title }}</span>
                                </div>
                                <p class="text-gray-400 text-xs mt-1">{{ $alert->created_at->diffForHumans() }}</p>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-gray-400 text-sm">Tidak ada alert</p>
                        @endif
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function pingNow() {
    const icon = document.getElementById('ping-icon');
    icon.classList.add('fa-spin');
    
    fetch('{{ route("admin.ip-monitor.ping", $ipMonitor) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        icon.classList.remove('fa-spin');
        if (data.success) {
            location.reload();
        }
    })
    .catch(() => {
        icon.classList.remove('fa-spin');
        alert('Gagal melakukan ping');
    });
}
</script>
@endsection
