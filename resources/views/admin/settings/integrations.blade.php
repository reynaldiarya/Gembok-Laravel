@extends('layouts.app')

@section('content')
@include('admin.partials.sidebar')
@include('admin.partials.topbar')

<main class="lg:ml-64 pt-16 min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-cyan-900">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-white">Pengaturan Integrasi</h1>
            <p class="text-gray-400 mt-1">Kelola koneksi ke layanan eksternal (Mikrotik, RADIUS, GenieACS, WhatsApp)</p>
        </div>

        <!-- Integration Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Mikrotik -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl border border-white/20 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="h-12 w-12 bg-gradient-to-br from-orange-400 to-red-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-server text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Mikrotik</h3>
                                <p class="text-sm text-gray-400">RouterOS API</p>
                            </div>
                        </div>
                        @if($integrations['mikrotik'] && $integrations['mikrotik']->enabled)
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">Aktif</span>
                        @else
                            <span class="px-2 py-1 bg-gray-500/20 text-gray-400 text-xs rounded-full">Nonaktif</span>
                        @endif
                    </div>
                    
                    @if($integrations['mikrotik'])
                        <div class="text-sm text-gray-400 mb-4">
                            <p><i class="fas fa-globe mr-2"></i>{{ $integrations['mikrotik']->getConfig('host', '-') }}:{{ $integrations['mikrotik']->getConfig('port', '-') }}</p>
                            @if($integrations['mikrotik']->last_tested_at)
                                <p class="mt-1">
                                    <i class="fas fa-clock mr-2"></i>Test: {{ $integrations['mikrotik']->last_tested_at->diffForHumans() }}
                                    @if($integrations['mikrotik']->last_test_success)
                                        <i class="fas fa-check-circle text-green-400 ml-1"></i>
                                    @else
                                        <i class="fas fa-times-circle text-red-400 ml-1"></i>
                                    @endif
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-500 mb-4">Belum dikonfigurasi</p>
                    @endif
                    
                    <a href="{{ route('admin.settings.mikrotik') }}" class="block w-full text-center py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition">
                        <i class="fas fa-cog mr-2"></i>Konfigurasi
                    </a>
                </div>
            </div>
            
            <!-- RADIUS -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl border border-white/20 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="h-12 w-12 bg-gradient-to-br from-purple-400 to-indigo-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shield-alt text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">RADIUS</h3>
                                <p class="text-sm text-gray-400">FreeRADIUS Database</p>
                            </div>
                        </div>
                        @if($integrations['radius'] && $integrations['radius']->enabled)
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">Aktif</span>
                        @else
                            <span class="px-2 py-1 bg-gray-500/20 text-gray-400 text-xs rounded-full">Nonaktif</span>
                        @endif
                    </div>
                    
                    @if($integrations['radius'])
                        <div class="text-sm text-gray-400 mb-4">
                            <p><i class="fas fa-database mr-2"></i>{{ $integrations['radius']->getConfig('host', '-') }}:{{ $integrations['radius']->getConfig('port', '-') }}</p>
                            @if($integrations['radius']->last_tested_at)
                                <p class="mt-1">
                                    <i class="fas fa-clock mr-2"></i>Test: {{ $integrations['radius']->last_tested_at->diffForHumans() }}
                                    @if($integrations['radius']->last_test_success)
                                        <i class="fas fa-check-circle text-green-400 ml-1"></i>
                                    @else
                                        <i class="fas fa-times-circle text-red-400 ml-1"></i>
                                    @endif
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-500 mb-4">Belum dikonfigurasi</p>
                    @endif
                    
                    <a href="{{ route('admin.settings.radius') }}" class="block w-full text-center py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition">
                        <i class="fas fa-cog mr-2"></i>Konfigurasi
                    </a>
                </div>
            </div>
            
            <!-- GenieACS -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl border border-white/20 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="h-12 w-12 bg-gradient-to-br from-blue-400 to-cyan-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-router text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">GenieACS</h3>
                                <p class="text-sm text-gray-400">TR-069 ACS</p>
                            </div>
                        </div>
                        @if($integrations['genieacs'] && $integrations['genieacs']->enabled)
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">Aktif</span>
                        @else
                            <span class="px-2 py-1 bg-gray-500/20 text-gray-400 text-xs rounded-full">Nonaktif</span>
                        @endif
                    </div>
                    
                    @if($integrations['genieacs'])
                        <div class="text-sm text-gray-400 mb-4">
                            <p><i class="fas fa-link mr-2"></i>{{ $integrations['genieacs']->getConfig('url', '-') }}</p>
                            @if($integrations['genieacs']->last_tested_at)
                                <p class="mt-1">
                                    <i class="fas fa-clock mr-2"></i>Test: {{ $integrations['genieacs']->last_tested_at->diffForHumans() }}
                                    @if($integrations['genieacs']->last_test_success)
                                        <i class="fas fa-check-circle text-green-400 ml-1"></i>
                                    @else
                                        <i class="fas fa-times-circle text-red-400 ml-1"></i>
                                    @endif
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-500 mb-4">Belum dikonfigurasi</p>
                    @endif
                    
                    <a href="{{ route('admin.settings.genieacs') }}" class="block w-full text-center py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                        <i class="fas fa-cog mr-2"></i>Konfigurasi
                    </a>
                </div>
            </div>
            
            <!-- WhatsApp -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl border border-white/20 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="h-12 w-12 bg-gradient-to-br from-green-400 to-emerald-600 rounded-lg flex items-center justify-center">
                                <i class="fab fa-whatsapp text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">WhatsApp</h3>
                                <p class="text-sm text-gray-400">Gateway API</p>
                            </div>
                        </div>
                        @if($integrations['whatsapp'] && $integrations['whatsapp']->enabled)
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">Aktif</span>
                        @else
                            <span class="px-2 py-1 bg-gray-500/20 text-gray-400 text-xs rounded-full">Nonaktif</span>
                        @endif
                    </div>
                    
                    @if($integrations['whatsapp'])
                        <div class="text-sm text-gray-400 mb-4">
                            <p><i class="fas fa-link mr-2"></i>{{ $integrations['whatsapp']->getConfig('api_url', '-') }}</p>
                            @if($integrations['whatsapp']->last_tested_at)
                                <p class="mt-1">
                                    <i class="fas fa-clock mr-2"></i>Test: {{ $integrations['whatsapp']->last_tested_at->diffForHumans() }}
                                    @if($integrations['whatsapp']->last_test_success)
                                        <i class="fas fa-check-circle text-green-400 ml-1"></i>
                                    @else
                                        <i class="fas fa-times-circle text-red-400 ml-1"></i>
                                    @endif
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-500 mb-4">Belum dikonfigurasi</p>
                    @endif
                    
                    <a href="{{ route('admin.settings.whatsapp') }}" class="block w-full text-center py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition">
                        <i class="fas fa-cog mr-2"></i>Konfigurasi
                    </a>
                </div>
            </div>
            
            <!-- Midtrans -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl border border-white/20 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="h-12 w-12 bg-gradient-to-br from-yellow-400 to-orange-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-credit-card text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Midtrans</h3>
                                <p class="text-sm text-gray-400">Payment Gateway</p>
                            </div>
                        </div>
                        @if($integrations['midtrans'] && $integrations['midtrans']->enabled)
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">Aktif</span>
                        @else
                            <span class="px-2 py-1 bg-gray-500/20 text-gray-400 text-xs rounded-full">Nonaktif</span>
                        @endif
                    </div>
                    
                    <p class="text-sm text-gray-500 mb-4">{{ $integrations['midtrans'] ? 'Dikonfigurasi' : 'Belum dikonfigurasi' }}</p>
                    
                    <a href="{{ route('admin.settings.midtrans') }}" class="block w-full text-center py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition">
                        <i class="fas fa-cog mr-2"></i>Konfigurasi
                    </a>
                </div>
            </div>
            
            <!-- Xendit -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl border border-white/20 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="h-12 w-12 bg-gradient-to-br from-indigo-400 to-violet-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-wallet text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Xendit</h3>
                                <p class="text-sm text-gray-400">Payment Gateway</p>
                            </div>
                        </div>
                        @if($integrations['xendit'] && $integrations['xendit']->enabled)
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">Aktif</span>
                        @else
                            <span class="px-2 py-1 bg-gray-500/20 text-gray-400 text-xs rounded-full">Nonaktif</span>
                        @endif
                    </div>
                    
                    <p class="text-sm text-gray-500 mb-4">{{ $integrations['xendit'] ? 'Dikonfigurasi' : 'Belum dikonfigurasi' }}</p>
                    
                    <a href="{{ route('admin.settings.xendit') }}" class="block w-full text-center py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg transition">
                        <i class="fas fa-cog mr-2"></i>Konfigurasi
                    </a>
                </div>
            </div>
            
            <!-- Duitku -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl border border-white/20 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="h-12 w-12 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-credit-card text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Duitku</h3>
                                <p class="text-sm text-gray-400">Payment Gateway</p>
                            </div>
                        </div>
                        @php $duitkuEnabled = \App\Models\AppSetting::getValue('duitku_enabled', 'false') === 'true'; @endphp
                        @if($duitkuEnabled)
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">Aktif</span>
                        @else
                            <span class="px-2 py-1 bg-gray-500/20 text-gray-400 text-xs rounded-full">Nonaktif</span>
                        @endif
                    </div>
                    
                    <p class="text-sm text-gray-500 mb-4">QRIS, VA, E-Wallet, Retail</p>
                    
                    <a href="{{ route('admin.settings.duitku') }}" class="block w-full text-center py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                        <i class="fas fa-cog mr-2"></i>Konfigurasi
                    </a>
                </div>
            </div>
            
        </div>
        
        <!-- Info -->
        <div class="mt-6 bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
            <div class="flex items-start space-x-3">
                <i class="fas fa-info-circle text-blue-400 mt-0.5"></i>
                <div class="text-sm text-blue-300">
                    <p class="font-semibold mb-1">Catatan:</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-200">
                        <li>Konfigurasi disimpan di database, tidak perlu edit file .env</li>
                        <li>Gunakan tombol "Test Koneksi" untuk memverifikasi konfigurasi</li>
                        <li>Pastikan firewall mengizinkan koneksi ke layanan eksternal</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
