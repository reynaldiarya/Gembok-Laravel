@extends('layouts.app')

@section('title', 'Pengaturan Duitku')

@section('content')
<div class="min-h-screen bg-gray-100">
    @include('admin.partials.sidebar')
    
    <div class="lg:pl-64">
        @include('admin.partials.topbar')
        
        <main class="p-6">
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Pengaturan Duitku</h1>
                        <p class="text-gray-600">Konfigurasi Payment Gateway Duitku</p>
                    </div>
                    <a href="{{ route('admin.settings.integrations') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        ← Kembali
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Settings Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6 bg-gradient-to-r from-blue-600 to-indigo-600">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-credit-card text-white text-xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-white">Duitku Payment Gateway</h2>
                                    <p class="text-blue-100">QRIS, Virtual Account, E-Wallet, Retail</p>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('admin.settings.duitku.save') }}" method="POST" class="p-6">
                            @csrf

                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-4">
                                    <label class="text-sm font-medium text-gray-700">Status</label>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="enabled" class="sr-only peer" 
                                               {{ $settings['enabled'] === 'true' ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        <span class="ml-3 text-sm font-medium text-gray-700">Aktif</span>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Merchant Code <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="merchant_code" value="{{ $settings['merchant_code'] }}" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Masukkan Merchant Code">
                                <p class="text-xs text-gray-500 mt-1">Dapatkan dari dashboard Duitku</p>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    API Key <span class="text-red-500">*</span>
                                </label>
                                <input type="password" name="api_key" value="{{ $settings['api_key'] }}" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Masukkan API Key">
                            </div>

                            <div class="mb-6">
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_production" id="is_production"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                           {{ $settings['is_production'] === 'true' ? 'checked' : '' }}>
                                    <label for="is_production" class="ml-2 text-sm text-gray-700">
                                        Mode Production
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Centang jika sudah siap untuk transaksi real</p>
                            </div>

                            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                                <h4 class="font-medium text-gray-700 mb-2">Callback URL</h4>
                                <div class="flex items-center">
                                    <code class="flex-1 px-3 py-2 bg-white border rounded text-sm text-gray-600">
                                        {{ config('app.url') }}/api/duitku/callback
                                    </code>
                                    <button type="button" onclick="copyCallback()" class="ml-2 px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Masukkan URL ini di dashboard Duitku</p>
                            </div>

                            <div class="flex items-center justify-between">
                                <button type="button" onclick="testConnection()" 
                                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                                    <i class="fas fa-plug mr-2"></i>Test Koneksi
                                </button>
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    <i class="fas fa-save mr-2"></i>Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info Panel -->
                <div class="space-y-6">
                    <!-- Payment Methods -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="font-semibold text-gray-800 mb-4">Metode Pembayaran</h3>
                        <div class="space-y-3">
                            <div class="flex items-center text-sm">
                                <i class="fas fa-qrcode text-purple-500 w-6"></i>
                                <span class="text-gray-600">QRIS (Semua E-Wallet)</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <i class="fas fa-university text-blue-500 w-6"></i>
                                <span class="text-gray-600">Virtual Account (BCA, Mandiri, BNI, BRI)</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <i class="fas fa-wallet text-green-500 w-6"></i>
                                <span class="text-gray-600">E-Wallet (OVO, DANA, ShopeePay)</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <i class="fas fa-store text-orange-500 w-6"></i>
                                <span class="text-gray-600">Retail (Indomaret, Alfamart)</span>
                            </div>
                        </div>
                    </div>

                    <!-- How to Get Credentials -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="font-semibold text-gray-800 mb-4">Cara Mendapatkan Kredensial</h3>
                        <ol class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start">
                                <span class="bg-blue-100 text-blue-600 rounded-full w-5 h-5 flex items-center justify-center text-xs mr-2 mt-0.5">1</span>
                                Daftar di <a href="https://duitku.com" target="_blank" class="text-blue-600 hover:underline">duitku.com</a>
                            </li>
                            <li class="flex items-start">
                                <span class="bg-blue-100 text-blue-600 rounded-full w-5 h-5 flex items-center justify-center text-xs mr-2 mt-0.5">2</span>
                                Login ke Dashboard Merchant
                            </li>
                            <li class="flex items-start">
                                <span class="bg-blue-100 text-blue-600 rounded-full w-5 h-5 flex items-center justify-center text-xs mr-2 mt-0.5">3</span>
                                Buka menu Project > API Keys
                            </li>
                            <li class="flex items-start">
                                <span class="bg-blue-100 text-blue-600 rounded-full w-5 h-5 flex items-center justify-center text-xs mr-2 mt-0.5">4</span>
                                Copy Merchant Code dan API Key
                            </li>
                            <li class="flex items-start">
                                <span class="bg-blue-100 text-blue-600 rounded-full w-5 h-5 flex items-center justify-center text-xs mr-2 mt-0.5">5</span>
                                Set Callback URL di dashboard Duitku
                            </li>
                        </ol>
                    </div>

                    <!-- Test Result -->
                    <div id="testResult" class="hidden bg-white rounded-xl shadow-md p-6">
                        <h3 class="font-semibold text-gray-800 mb-4">Hasil Test</h3>
                        <div id="testResultContent"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function copyCallback() {
    const url = '{{ config("app.url") }}/api/duitku/callback';
    navigator.clipboard.writeText(url).then(() => {
        showToast('success', 'Callback URL berhasil disalin');
    });
}

function testConnection() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Testing...';

    fetch('{{ route("admin.settings.duitku.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('testResult');
        const contentDiv = document.getElementById('testResultContent');
        resultDiv.classList.remove('hidden');

        if (data.success) {
            contentDiv.innerHTML = `
                <div class="text-green-600">
                    <i class="fas fa-check-circle text-2xl mb-2"></i>
                    <p class="font-medium">${data.message}</p>
                    <p class="text-sm text-gray-500 mt-1">${data.methods_count} metode pembayaran tersedia</p>
                </div>
            `;
        } else {
            contentDiv.innerHTML = `
                <div class="text-red-600">
                    <i class="fas fa-times-circle text-2xl mb-2"></i>
                    <p class="font-medium">Koneksi Gagal</p>
                    <p class="text-sm mt-1">${data.message}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plug mr-2"></i>Test Koneksi';
    });
}
</script>
@endsection
