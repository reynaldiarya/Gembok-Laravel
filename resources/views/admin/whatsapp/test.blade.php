@extends('layouts.app')

@section('title', 'Test WhatsApp')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, selectedType: 'invoice', showCustomMessage: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Test Notifikasi WhatsApp</h1>
                    <p class="text-gray-600 mt-1">Kirim notifikasi test ke pelanggan tertentu</p>
                </div>
                <a href="{{ route('admin.whatsapp.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Test Form -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                        <h5 class="text-white font-bold text-lg">
                            <i class="fab fa-whatsapp mr-2"></i>Kirim Test Notifikasi
                        </h5>
                    </div>
                    <div class="p-6">
                        <form id="testForm" class="space-y-4">
                            @csrf
                            <!-- Pilih Pelanggan -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Pelanggan</label>
                                <select name="customer_id" id="customer_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" required>
                                    <option value="">-- Pilih Pelanggan --</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" data-phone="{{ $customer->phone }}" data-package="{{ $customer->package->name ?? '-' }}">
                                            {{ $customer->name }} - {{ $customer->phone }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Info Pelanggan -->
                            <div id="customerInfo" class="hidden bg-gray-50 rounded-lg p-4">
                                <h6 class="font-medium text-gray-700 mb-2">Info Pelanggan</h6>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <span class="text-gray-500">Nama:</span>
                                        <span id="infoName" class="font-medium text-gray-900"></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Telepon:</span>
                                        <span id="infoPhone" class="font-medium text-gray-900"></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Paket:</span>
                                        <span id="infoPackage" class="font-medium text-gray-900"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipe Notifikasi -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Notifikasi</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition" :class="selectedType === 'invoice' ? 'border-green-500 bg-green-50' : 'border-gray-300'">
                                        <input type="radio" name="type" value="invoice" x-model="selectedType" class="hidden">
                                        <i class="fas fa-file-invoice text-blue-500 mr-2"></i>
                                        <span class="text-sm">Invoice Baru</span>
                                    </label>
                                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition" :class="selectedType === 'reminder' ? 'border-green-500 bg-green-50' : 'border-gray-300'">
                                        <input type="radio" name="type" value="reminder" x-model="selectedType" class="hidden">
                                        <i class="fas fa-bell text-yellow-500 mr-2"></i>
                                        <span class="text-sm">Pengingat</span>
                                    </label>
                                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition" :class="selectedType === 'suspension' ? 'border-green-500 bg-green-50' : 'border-gray-300'">
                                        <input type="radio" name="type" value="suspension" x-model="selectedType" class="hidden">
                                        <i class="fas fa-ban text-red-500 mr-2"></i>
                                        <span class="text-sm">Penangguhan</span>
                                    </label>
                                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition" :class="selectedType === 'custom' ? 'border-green-500 bg-green-50' : 'border-gray-300'">
                                        <input type="radio" name="type" value="custom" x-model="selectedType" class="hidden">
                                        <i class="fas fa-edit text-purple-500 mr-2"></i>
                                        <span class="text-sm">Custom</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Custom Message -->
                            <div x-show="selectedType === 'custom'" x-transition>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pesan Custom</label>
                                <textarea name="message" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Tulis pesan custom..."></textarea>
                                <p class="text-xs text-gray-500 mt-1">Gunakan *text* untuk bold, _text_ untuk italic</p>
                            </div>

                            <button type="submit" id="sendBtn" class="w-full bg-green-500 text-white px-4 py-3 rounded-lg hover:bg-green-600 transition font-medium">
                                <i class="fab fa-whatsapp mr-2"></i>Kirim Test Notifikasi
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Preview & Info -->
                <div class="space-y-6">
                    <!-- Preview Template -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h5 class="font-bold text-gray-900"><i class="fas fa-eye mr-2 text-cyan-600"></i>Preview Template</h5>
                        </div>
                        <div class="p-6">
                            <!-- Invoice Template -->
                            <div x-show="selectedType === 'invoice'" class="bg-gray-50 rounded-lg p-4">
                                <pre class="text-sm text-gray-700 whitespace-pre-wrap">Halo *{nama}*,

Tagihan internet Anda telah terbit:

📋 *Invoice:* INV-XXXXX
📦 *Paket:* {paket}
💰 *Total:* Rp {amount}
📅 *Jatuh Tempo:* {due_date}

Silakan lakukan pembayaran sebelum jatuh tempo.

Terima kasih,
*{{ companyName() }}*</pre>
                            </div>

                            <!-- Reminder Template -->
                            <div x-show="selectedType === 'reminder'" class="bg-gray-50 rounded-lg p-4">
                                <pre class="text-sm text-gray-700 whitespace-pre-wrap">⚠️ *Pengingat Pembayaran*

Halo *{nama}*,

Tagihan Anda belum dibayar:

📋 *Invoice:* INV-XXXXX
💰 *Total:* Rp {amount}
📅 *Jatuh Tempo:* {due_date}

Mohon segera lakukan pembayaran untuk menghindari pemutusan layanan.

*{{ companyName() }}*</pre>
                            </div>

                            <!-- Suspension Template -->
                            <div x-show="selectedType === 'suspension'" class="bg-gray-50 rounded-lg p-4">
                                <pre class="text-sm text-gray-700 whitespace-pre-wrap">🚫 *Pemberitahuan Penangguhan Layanan*

Halo *{nama}*,

Layanan internet Anda telah ditangguhkan karena tunggakan pembayaran.

Silakan hubungi kami atau lakukan pembayaran untuk mengaktifkan kembali layanan Anda.

*{{ companyName() }}*</pre>
                            </div>

                            <!-- Custom Template -->
                            <div x-show="selectedType === 'custom'" class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-500 italic">Pesan custom akan dikirim sesuai yang Anda tulis.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Result -->
                    <div id="resultCard" class="hidden bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h5 class="font-bold text-gray-900"><i class="fas fa-check-circle mr-2 text-green-600"></i>Hasil</h5>
                        </div>
                        <div class="p-6">
                            <div id="resultContent"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('customer_id').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const infoDiv = document.getElementById('customerInfo');
    
    if (this.value) {
        document.getElementById('infoName').textContent = selected.text.split(' - ')[0];
        document.getElementById('infoPhone').textContent = selected.dataset.phone;
        document.getElementById('infoPackage').textContent = selected.dataset.package;
        infoDiv.classList.remove('hidden');
    } else {
        infoDiv.classList.add('hidden');
    }
});

document.getElementById('testForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('sendBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
    btn.disabled = true;
    
    const formData = new FormData(this);
    
    fetch('{{ route("admin.whatsapp.test.send") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            customer_id: formData.get('customer_id'),
            type: formData.get('type'),
            message: formData.get('message')
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        const resultCard = document.getElementById('resultCard');
        const resultContent = document.getElementById('resultContent');
        
        resultCard.classList.remove('hidden');
        
        if (data.success) {
            resultContent.innerHTML = `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 text-2xl mr-3"></i>
                        <div>
                            <p class="font-medium text-green-800">Berhasil!</p>
                            <p class="text-sm text-green-600">${data.message || 'Pesan berhasil dikirim'}</p>
                        </div>
                    </div>
                </div>
            `;
        } else {
            resultContent.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-times-circle text-red-500 text-2xl mr-3"></i>
                        <div>
                            <p class="font-medium text-red-800">Gagal!</p>
                            <p class="text-sm text-red-600">${data.message || 'Gagal mengirim pesan'}</p>
                        </div>
                    </div>
                </div>
            `;
        }
    })
    .catch(error => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        alert('Error: ' + error.message);
    });
});
</script>
@endpush
@endsection
