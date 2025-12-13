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
                    <h1 class="text-2xl font-bold text-white">Konfigurasi Mikrotik</h1>
                    <p class="text-gray-400 mt-1">Pengaturan koneksi RouterOS API</p>
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
                    
                    @if($errors->any())
                        <div class="mb-4 p-4 bg-red-500/20 border border-red-500/30 rounded-lg text-red-400">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form action="{{ route('admin.settings.mikrotik.save') }}" method="POST" id="mikrotikForm">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Nama Router</label>
                                <input type="text" name="name" value="{{ $setting->name ?? 'Mikrotik Router' }}" 
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white focus:outline-none focus:border-cyan-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Host / IP Address <span class="text-red-400">*</span></label>
                                <input type="text" name="host" id="host" value="{{ $setting->getConfig('host') }}" required
                                       placeholder="192.168.1.1"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white focus:outline-none focus:border-cyan-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Port API <span class="text-red-400">*</span></label>
                                <input type="number" name="port" id="port" value="{{ $setting->getConfig('port', 8728) }}" required
                                       placeholder="8728"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white focus:outline-none focus:border-cyan-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Username <span class="text-red-400">*</span></label>
                                <input type="text" name="username" id="username" value="{{ $setting->getConfig('username') }}" required
                                       placeholder="admin"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white focus:outline-none focus:border-cyan-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Password <span class="text-red-400">*</span></label>
                                <input type="password" name="password" id="password" value="{{ $setting->getConfig('password') }}" required
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white focus:outline-none focus:border-cyan-500">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" name="use_ssl" id="use_ssl" value="1" {{ $setting->getConfig('use_ssl') ? 'checked' : '' }}
                                           class="w-5 h-5 rounded border-white/20 bg-white/5 text-cyan-500 focus:ring-cyan-500">
                                    <span class="text-gray-300">Gunakan SSL (Port 8729)</span>
                                </label>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" name="enabled" value="1" {{ $setting->enabled ? 'checked' : '' }}
                                           class="w-5 h-5 rounded border-white/20 bg-white/5 text-cyan-500 focus:ring-cyan-500">
                                    <span class="text-gray-300">Aktifkan Integrasi Mikrotik</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-4 mt-6">
                            <button type="submit" class="px-6 py-2 bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg transition">
                                <i class="fas fa-save mr-2"></i>Simpan
                            </button>
                            <button type="button" onclick="testConnection()" class="px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition">
                                <i class="fas fa-plug mr-2"></i>Test Koneksi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Test Result -->
            <div>
                <div class="bg-white/10 backdrop-blur-lg rounded-xl border border-white/20 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">
                        <i class="fas fa-vial mr-2"></i>Hasil Test
                    </h3>
                    
                    <div id="testResult" class="hidden">
                        <div id="testSuccess" class="hidden p-4 bg-green-500/20 border border-green-500/30 rounded-lg">
                            <p class="text-green-400 font-semibold mb-2"><i class="fas fa-check-circle mr-2"></i>Koneksi Berhasil!</p>
                            <div class="text-sm text-green-300 space-y-1">
                                <p><strong>Identity:</strong> <span id="resIdentity">-</span></p>
                                <p><strong>Version:</strong> <span id="resVersion">-</span></p>
                                <p><strong>Uptime:</strong> <span id="resUptime">-</span></p>
                                <p><strong>CPU Load:</strong> <span id="resCpu">-</span>%</p>
                            </div>
                        </div>
                        <div id="testError" class="hidden p-4 bg-red-500/20 border border-red-500/30 rounded-lg">
                            <p class="text-red-400 font-semibold mb-2"><i class="fas fa-times-circle mr-2"></i>Koneksi Gagal</p>
                            <p class="text-sm text-red-300" id="errorMessage">-</p>
                        </div>
                    </div>
                    
                    <div id="testLoading" class="hidden text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-cyan-400"></i>
                        <p class="text-gray-400 mt-2">Menghubungkan...</p>
                    </div>
                    
                    <div id="testPlaceholder" class="text-center py-8 text-gray-500">
                        <i class="fas fa-plug text-3xl mb-2"></i>
                        <p>Klik "Test Koneksi" untuk memverifikasi</p>
                    </div>
                    
                    @if($setting->last_tested_at)
                        <div class="mt-4 pt-4 border-t border-white/10 text-sm text-gray-400">
                            <p>Test terakhir: {{ $setting->last_tested_at->format('d/m/Y H:i') }}</p>
                            <p>Status: 
                                @if($setting->last_test_success)
                                    <span class="text-green-400">Berhasil</span>
                                @else
                                    <span class="text-red-400">Gagal</span>
                                @endif
                            </p>
                            @if($setting->last_test_message)
                                <p class="mt-1 text-xs">{{ $setting->last_test_message }}</p>
                            @endif
                        </div>
                    @endif
                </div>
                
                <!-- Help -->
                <div class="mt-4 bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                    <h4 class="text-blue-400 font-semibold mb-2"><i class="fas fa-info-circle mr-2"></i>Bantuan</h4>
                    <ul class="text-sm text-blue-300 space-y-1">
                        <li>• Port default API: 8728</li>
                        <li>• Port SSL: 8729</li>
                        <li>• Pastikan API service aktif di Mikrotik</li>
                        <li>• User harus punya akses API</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function testConnection() {
    const host = document.getElementById('host').value;
    const port = document.getElementById('port').value;
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const useSsl = document.getElementById('use_ssl').checked;
    
    if (!host || !port || !username || !password) {
        alert('Mohon lengkapi semua field yang wajib diisi');
        return;
    }
    
    document.getElementById('testPlaceholder').classList.add('hidden');
    document.getElementById('testResult').classList.add('hidden');
    document.getElementById('testLoading').classList.remove('hidden');
    
    fetch('{{ route("admin.settings.mikrotik.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ host, port, username, password, use_ssl: useSsl })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('testLoading').classList.add('hidden');
        document.getElementById('testResult').classList.remove('hidden');
        
        if (data.success) {
            document.getElementById('testSuccess').classList.remove('hidden');
            document.getElementById('testError').classList.add('hidden');
            document.getElementById('resIdentity').textContent = data.data.identity || '-';
            document.getElementById('resVersion').textContent = data.data.version || '-';
            document.getElementById('resUptime').textContent = data.data.uptime || '-';
            document.getElementById('resCpu').textContent = data.data.cpu_load || '-';
        } else {
            document.getElementById('testSuccess').classList.add('hidden');
            document.getElementById('testError').classList.remove('hidden');
            document.getElementById('errorMessage').textContent = data.message;
        }
    })
    .catch(error => {
        document.getElementById('testLoading').classList.add('hidden');
        document.getElementById('testResult').classList.remove('hidden');
        document.getElementById('testSuccess').classList.add('hidden');
        document.getElementById('testError').classList.remove('hidden');
        document.getElementById('errorMessage').textContent = 'Network error: ' + error.message;
    });
}
</script>
@endsection
