@extends('layouts.app')

@section('content')
@include('admin.partials.sidebar')
@include('admin.partials.topbar')

<main class="lg:ml-64 pt-16 min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-cyan-900">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.settings.integrations') }}" class="text-gray-400 hover:text-white">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-white">Konfigurasi WhatsApp</h1>
                    <p class="text-gray-400 mt-1">Pengaturan WhatsApp Gateway API</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form -->
            <div class="lg:col-span-2">
                <div class="bg-white/10 backdrop-blur-lg rounded-xl border border-white/20 p-6">
                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-500/20 border border-green-500/30 rounded-lg text-green-400">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </div>
                    @endif
                    
                    <form action="{{ route('admin.settings.whatsapp.save') }}" method="POST">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Nama Gateway</label>
                                <input type="text" name="name" value="{{ $setting->name ?? 'WhatsApp Gateway' }}" 
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-300 mb-2">API URL <span class="text-red-400">*</span></label>
                                <input type="url" name="api_url" id="api_url" value="{{ $setting->getConfig('api_url') }}" required
                                       placeholder="https://api.fonnte.com"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-300 mb-2">API Key <span class="text-red-400">*</span></label>
                                <input type="text" name="api_key" id="api_key" value="{{ $setting->getConfig('api_key') }}" required
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Nomor Pengirim</label>
                                <input type="text" name="sender" value="{{ $setting->getConfig('sender') }}"
                                       placeholder="628123456789"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Nomor Admin</label>
                                <input type="text" name="admin_phone" value="{{ $setting->getConfig('admin_phone') }}"
                                       placeholder="628123456789"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" name="enabled" value="1" {{ $setting->enabled ? 'checked' : '' }}
                                           class="w-5 h-5 rounded border-white/20 bg-white/5 text-cyan-500">
                                    <span class="text-gray-300">Aktifkan WhatsApp Gateway</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-4 mt-6">
                            <button type="submit" class="px-6 py-2 bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg">
                                <i class="fas fa-save mr-2"></i>Simpan
                            </button>
                            <button type="button" onclick="showTestModal()" class="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg">
                                <i class="fab fa-whatsapp mr-2"></i>Test Kirim
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Test Result -->
            <div>
                <div class="bg-white/10 backdrop-blur-lg rounded-xl border border-white/20 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4"><i class="fas fa-vial mr-2"></i>Hasil Test</h3>
                    
                    <div id="testResult" class="hidden">
                        <div id="testSuccess" class="hidden p-4 bg-green-500/20 border border-green-500/30 rounded-lg">
                            <p class="text-green-400 font-semibold"><i class="fas fa-check-circle mr-2"></i>Pesan Terkirim!</p>
                        </div>
                        <div id="testError" class="hidden p-4 bg-red-500/20 border border-red-500/30 rounded-lg">
                            <p class="text-red-400 font-semibold mb-2"><i class="fas fa-times-circle mr-2"></i>Gagal</p>
                            <p class="text-sm text-red-300" id="errorMessage">-</p>
                        </div>
                    </div>
                    
                    <div id="testLoading" class="hidden text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-cyan-400"></i>
                        <p class="text-gray-400 mt-2">Mengirim...</p>
                    </div>
                    
                    <div id="testPlaceholder" class="text-center py-8 text-gray-500">
                        <i class="fab fa-whatsapp text-3xl mb-2"></i>
                        <p>Klik "Test Kirim" untuk mengirim pesan test</p>
                    </div>
                    
                    @if($setting->last_tested_at)
                        <div class="mt-4 pt-4 border-t border-white/10 text-sm text-gray-400">
                            <p>Test terakhir: {{ $setting->last_tested_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif
                </div>
                
                <div class="mt-4 bg-green-500/10 border border-green-500/30 rounded-lg p-4">
                    <h4 class="text-green-400 font-semibold mb-2"><i class="fas fa-info-circle mr-2"></i>Provider Didukung</h4>
                    <ul class="text-sm text-green-300 space-y-1">
                        <li>• Fonnte</li>
                        <li>• Wablas</li>
                        <li>• Woowa</li>
                        <li>• Custom API</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Test Modal -->
<div id="testModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-slate-800 rounded-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-white mb-4">Test Kirim WhatsApp</h3>
        <div class="mb-4">
            <label class="block text-sm text-gray-300 mb-2">Nomor Tujuan</label>
            <input type="text" id="test_number" placeholder="628123456789"
                   class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
        </div>
        <div class="flex space-x-3">
            <button onclick="testConnection()" class="flex-1 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg">Kirim</button>
            <button onclick="hideTestModal()" class="flex-1 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg">Batal</button>
        </div>
    </div>
</div>

<script>
function showTestModal() { document.getElementById('testModal').classList.remove('hidden'); document.getElementById('testModal').classList.add('flex'); }
function hideTestModal() { document.getElementById('testModal').classList.add('hidden'); document.getElementById('testModal').classList.remove('flex'); }

function testConnection() {
    const api_url = document.getElementById('api_url').value;
    const api_key = document.getElementById('api_key').value;
    const test_number = document.getElementById('test_number').value;
    
    if (!api_url || !api_key || !test_number) { alert('Mohon lengkapi semua field'); return; }
    
    hideTestModal();
    document.getElementById('testPlaceholder').classList.add('hidden');
    document.getElementById('testResult').classList.add('hidden');
    document.getElementById('testLoading').classList.remove('hidden');
    
    fetch('{{ route("admin.settings.whatsapp.test") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ api_url, api_key, test_number })
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('testLoading').classList.add('hidden');
        document.getElementById('testResult').classList.remove('hidden');
        if (data.success) {
            document.getElementById('testSuccess').classList.remove('hidden');
            document.getElementById('testError').classList.add('hidden');
        } else {
            document.getElementById('testSuccess').classList.add('hidden');
            document.getElementById('testError').classList.remove('hidden');
            document.getElementById('errorMessage').textContent = data.message;
        }
    })
    .catch(e => {
        document.getElementById('testLoading').classList.add('hidden');
        document.getElementById('testResult').classList.remove('hidden');
        document.getElementById('testError').classList.remove('hidden');
        document.getElementById('errorMessage').textContent = e.message;
    });
}
</script>
@endsection