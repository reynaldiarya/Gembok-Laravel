@extends('layouts.app')

@section('content')
@include('admin.partials.sidebar')
@include('admin.partials.topbar')

<main class="lg:ml-64 pt-16 min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-cyan-900">
    <div class="p-6">
        <div class="mb-6">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.settings.integrations') }}" class="text-gray-400 hover:text-white">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-white">Konfigurasi Midtrans</h1>
                    <p class="text-gray-400 mt-1">Pengaturan Payment Gateway Midtrans</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white/10 backdrop-blur-lg rounded-xl border border-white/20 p-6">
                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-500/20 border border-green-500/30 rounded-lg text-green-400">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </div>
                    @endif
                    
                    <form action="{{ route('admin.settings.midtrans.save') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Merchant ID</label>
                                <input type="text" name="merchant_id" value="{{ $setting->getConfig('merchant_id') }}"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Server Key <span class="text-red-400">*</span></label>
                                <input type="password" name="server_key" value="{{ $setting->getConfig('server_key') }}" required
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Client Key <span class="text-red-400">*</span></label>
                                <input type="text" name="client_key" value="{{ $setting->getConfig('client_key') }}" required
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                            </div>

                            <div class="md:col-span-2">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" name="is_production" value="1" {{ $setting->getConfig('is_production') ? 'checked' : '' }}
                                           class="w-5 h-5 rounded border-white/20 bg-white/5 text-cyan-500">
                                    <span class="text-gray-300">Mode Production (uncheck untuk Sandbox)</span>
                                </label>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" name="enabled" value="1" {{ $setting->enabled ? 'checked' : '' }}
                                           class="w-5 h-5 rounded border-white/20 bg-white/5 text-cyan-500">
                                    <span class="text-gray-300">Aktifkan Midtrans</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" class="px-6 py-2 bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg">
                                <i class="fas fa-save mr-2"></i>Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div>
                <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
                    <h4 class="text-yellow-400 font-semibold mb-2"><i class="fas fa-info-circle mr-2"></i>Cara Mendapatkan Key</h4>
                    <ol class="text-sm text-yellow-300 space-y-1 list-decimal list-inside">
                        <li>Login ke dashboard.midtrans.com</li>
                        <li>Pilih Environment (Sandbox/Production)</li>
                        <li>Settings → Access Keys</li>
                        <li>Copy Server Key dan Client Key</li>
                    </ol>
                </div>
                
                <div class="mt-4 bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                    <h4 class="text-blue-400 font-semibold mb-2"><i class="fas fa-link mr-2"></i>Callback URL</h4>
                    <p class="text-sm text-blue-300 break-all">{{ url('/api/payment/midtrans/callback') }}</p>
                    <p class="text-xs text-gray-500 mt-2">Set URL ini di dashboard Midtrans</p>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection