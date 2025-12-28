@extends('layouts.app')

@section('title', 'IP Monitor Alerts')

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
                        <h1 class="text-2xl font-bold text-white">Network Alerts</h1>
                        <p class="text-gray-400">Semua alert dari IP Monitor</p>
                    </div>
                </div>
                <div class="mt-4 md:mt-0">
                    <form action="{{ route('admin.ip-monitor.alerts.read-all') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-check-double mr-2"></i>Mark All as Read
                        </button>
                    </form>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-800 rounded-xl p-4 mb-6 border border-gray-700">
                <form method="GET" class="flex flex-wrap gap-4">
                    <select name="type" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                        <option value="">Semua Type</option>
                        <option value="down" {{ request('type') == 'down' ? 'selected' : '' }}>Down</option>
                        <option value="recovery" {{ request('type') == 'recovery' ? 'selected' : '' }}>Recovery</option>
                        <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Warning</option>
                    </select>
                    <label class="flex items-center text-gray-300">
                        <input type="checkbox" name="unread" value="1" {{ request('unread') ? 'checked' : '' }}
                               class="w-4 h-4 text-cyan-600 bg-gray-700 border-gray-600 rounded mr-2">
                        Hanya Unread
                    </label>
                    <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-2 rounded-lg transition">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </form>
            </div>

            <!-- Alerts List -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <div class="divide-y divide-gray-700">
                    @forelse($alerts as $alert)
                    <div class="p-4 hover:bg-gray-700/50 {{ !$alert->is_read ? 'bg-gray-700/30' : '' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4">
                                <div class="h-10 w-10 rounded-full bg-{{ $alert->type_color }}-900 flex items-center justify-center">
                                    <i class="fas {{ $alert->type_icon }} text-{{ $alert->type_color }}-400"></i>
                                </div>
                                <div>
                                    <h4 class="text-white font-medium">{{ $alert->title }}</h4>
                                    <p class="text-gray-400 text-sm mt-1">{{ $alert->message }}</p>
                                    <div class="flex items-center space-x-4 mt-2">
                                        <span class="text-gray-500 text-xs">{{ $alert->created_at->format('d/m/Y H:i:s') }}</span>
                                        <span class="text-gray-500 text-xs">{{ $alert->created_at->diffForHumans() }}</span>
                                        @if($alert->resolved_at)
                                        <span class="text-green-400 text-xs">
                                            <i class="fas fa-check mr-1"></i>Resolved {{ $alert->resolved_at->diffForHumans() }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if(!$alert->is_read)
                                <span class="w-2 h-2 bg-cyan-400 rounded-full"></span>
                                <form action="{{ route('admin.ip-monitor.alert.read', $alert) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-cyan-400 hover:text-cyan-300 text-sm">
                                        Mark as read
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-8 text-center text-gray-400">
                        <i class="fas fa-bell-slash text-4xl mb-3 opacity-50"></i>
                        <p>Tidak ada alert</p>
                    </div>
                    @endforelse
                </div>
                @if($alerts->hasPages())
                <div class="px-4 py-3 border-t border-gray-700">
                    {{ $alerts->links() }}
                </div>
                @endif
            </div>
        </main>
    </div>
</div>
@endsection
