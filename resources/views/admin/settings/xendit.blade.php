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
                    <h1 class="text-2xl font-bold text-white">Konfigurasi Xendit</h1>
                    <p class="text-gray-400 mt-1">Pengaturan Payment Gateway Xendit</p>
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
                    
                    <form action="{{ route('admin.settings.xendit.save') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Secret Key <span class="text-red-400">*</span></label>
                                <input type="password" name="secret_key" value="{{ $setting->getConfig('secret_key') }}" required
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Public Key</label>
                                <input type="text" name="public_key" value="{{ $setting->getConfig('public_key') }}"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Callback Token</label>
                                <input type="text" name="callback_token" value="{{ $setting->getConfig('callback_token') }}"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                            </div>

                            <div class="md:col-span-2">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" name="enabled" value="1" {{ $setting->enabled ? 'checked' : '' }}
                                           class="w-5 h-5 rounded border-white/20 bg-white/5 text-cyan-500">
                                    <span class="text-gray-300">Aktifkan Xendit</span>
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
                <div class="bg-indigo-500/10 border border-indigo-500/30 rounded-lg p-4">
                    <h4 class="text-indigo-400 font-semibold mb-2"><i class="fas fa-info-circle mr-2"></i>Cara Mendapatkan Key</h4>
                    <ol class="text-sm text-indigo-300 space-y-1 list-decimal list-inside">
                        <li>Login ke dashboard.xendit.co</li>
                        <li>Settings → API Keys</li>
                        <li>Generate Secret Key</li>
                        <li>Copy dan simpan dengan aman</li>
                    </ol>
                </div>
                
                <div class="mt-4 bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                    <h4 class="text-blue-400 font-semibold mb-2"><i class="fas fa-link mr-2"></i>Callback URL</h4>
                    <p class="text-sm text-blue-300 break-all">{{ url('/api/payment/xendit/callback') }}</p>
                    <p class="text-xs text-gray-500 mt-2">Set URL ini di dashboard Xendit</p>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection