@extends('layouts.customer')

@section('title', 'Detail Invoice')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Detail Invoice</h1>
        <a href="{{ route('customer.invoices') }}" class="text-cyan-600 hover:text-cyan-700">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <!-- Invoice Header -->
        <div class="flex justify-between items-start mb-6 pb-6 border-b">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ companyName() }}</h2>
                <p class="text-gray-500 text-sm">{{ companyAddress() }}</p>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ $invoice->status === 'paid' ? 'LUNAS' : 'BELUM BAYAR' }}
                </span>
            </div>
        </div>

        <!-- Invoice Info -->
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <p class="text-sm text-gray-500">No. Invoice</p>
                <p class="font-mono font-bold text-gray-900">{{ $invoice->invoice_number }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Tanggal</p>
                <p class="font-medium text-gray-900">{{ $invoice->created_at->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Jatuh Tempo</p>
                <p class="font-medium {{ $invoice->isOverdue() ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '-' }}
                </p>
            </div>
            @if($invoice->paid_at)
            <div class="text-right">
                <p class="text-sm text-gray-500">Tanggal Bayar</p>
                <p class="font-medium text-green-600">{{ $invoice->paid_at->format('d M Y H:i') }}</p>
            </div>
            @endif
        </div>

        <!-- Invoice Items -->
        <div class="mb-6">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Deskripsi</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="px-4 py-4">
                            <p class="font-medium text-gray-900">{{ $invoice->package->name ?? 'Layanan Internet' }}</p>
                            <p class="text-sm text-gray-500">{{ $invoice->description ?? 'Tagihan bulanan' }}</p>
                        </td>
                        <td class="px-4 py-4 text-right font-medium text-gray-900">
                            Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @if($invoice->tax_amount > 0)
                    <tr>
                        <td class="px-4 py-4 text-right text-gray-600">PPN</td>
                        <td class="px-4 py-4 text-right font-medium text-gray-900">
                            Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endif
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td class="px-4 py-4 text-right font-bold text-gray-900">TOTAL</td>
                        <td class="px-4 py-4 text-right font-bold text-xl text-cyan-600">
                            Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Payment Actions -->
        @if($invoice->status === 'unpaid')
            @php $duitkuEnabled = \App\Models\AppSetting::getValue('duitku_enabled', 'false') === 'true'; @endphp
            
            @if($invoice->payment_url)
                <!-- Already has payment link -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-blue-800 font-medium mb-2">Link pembayaran sudah dibuat</p>
                    <a href="{{ $invoice->payment_url }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-external-link-alt mr-2"></i>Lanjutkan Pembayaran
                    </a>
                </div>
            @elseif($duitkuEnabled)
                <div class="border-t pt-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Pilih Metode Pembayaran</h3>
                    
                    <div class="space-y-4" id="paymentMethods">
                        <!-- QRIS -->
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-2">QRIS (Semua E-Wallet)</p>
                            <button type="button" onclick="selectPayment('SP')" class="w-full flex items-center p-4 border-2 border-gray-200 rounded-xl hover:border-cyan-500 hover:bg-cyan-50 transition">
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-qrcode text-purple-600 text-xl"></i>
                                </div>
                                <div class="text-left flex-1">
                                    <p class="font-semibold text-gray-900">QRIS</p>
                                    <p class="text-sm text-gray-500">Scan dengan OVO, GoPay, DANA, dll</p>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </button>
                        </div>

                        <!-- Virtual Account -->
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-2">Virtual Account</p>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                <button type="button" onclick="selectPayment('BC')" class="flex items-center p-3 border-2 border-gray-200 rounded-xl hover:border-cyan-500 hover:bg-cyan-50 transition">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <span class="text-blue-600 font-bold text-xs">BCA</span>
                                    </div>
                                    <span class="font-medium text-gray-900 text-sm">BCA</span>
                                </button>
                                <button type="button" onclick="selectPayment('M2')" class="flex items-center p-3 border-2 border-gray-200 rounded-xl hover:border-cyan-500 hover:bg-cyan-50 transition">
                                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                                        <span class="text-yellow-600 font-bold text-xs">MDR</span>
                                    </div>
                                    <span class="font-medium text-gray-900 text-sm">Mandiri</span>
                                </button>
                                <button type="button" onclick="selectPayment('I1')" class="flex items-center p-3 border-2 border-gray-200 rounded-xl hover:border-cyan-500 hover:bg-cyan-50 transition">
                                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                                        <span class="text-orange-600 font-bold text-xs">BNI</span>
                                    </div>
                                    <span class="font-medium text-gray-900 text-sm">BNI</span>
                                </button>
                                <button type="button" onclick="selectPayment('BR')" class="flex items-center p-3 border-2 border-gray-200 rounded-xl hover:border-cyan-500 hover:bg-cyan-50 transition">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <span class="text-blue-600 font-bold text-xs">BRI</span>
                                    </div>
                                    <span class="font-medium text-gray-900 text-sm">BRI</span>
                                </button>
                            </div>
                        </div>

                        <!-- E-Wallet -->
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-2">E-Wallet</p>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" onclick="selectPayment('OV')" class="flex flex-col items-center p-3 border-2 border-gray-200 rounded-xl hover:border-cyan-500 hover:bg-cyan-50 transition">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-2">
                                        <span class="text-purple-600 font-bold text-xs">OVO</span>
                                    </div>
                                    <span class="font-medium text-gray-900 text-xs">OVO</span>
                                </button>
                                <button type="button" onclick="selectPayment('DA')" class="flex flex-col items-center p-3 border-2 border-gray-200 rounded-xl hover:border-cyan-500 hover:bg-cyan-50 transition">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-2">
                                        <span class="text-blue-600 font-bold text-xs">DANA</span>
                                    </div>
                                    <span class="font-medium text-gray-900 text-xs">DANA</span>
                                </button>
                                <button type="button" onclick="selectPayment('SA')" class="flex flex-col items-center p-3 border-2 border-gray-200 rounded-xl hover:border-cyan-500 hover:bg-cyan-50 transition">
                                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mb-2">
                                        <span class="text-orange-600 font-bold text-xs">SPay</span>
                                    </div>
                                    <span class="font-medium text-gray-900 text-xs">ShopeePay</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div id="paymentLoading" class="hidden text-center py-8">
                        <i class="fas fa-spinner fa-spin text-4xl text-cyan-600 mb-4"></i>
                        <p class="text-gray-600">Memproses pembayaran...</p>
                    </div>
                </div>
            @else
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-yellow-800">Pembayaran online belum tersedia. Silakan hubungi admin untuk informasi pembayaran.</p>
                </div>
            @endif
        @else
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 text-2xl mr-3"></i>
                    <div>
                        <p class="font-semibold text-green-800">Invoice sudah dibayar</p>
                        @if($invoice->payment_method)
                            <p class="text-sm text-green-600">Via: {{ $invoice->payment_method }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
function selectPayment(method) {
    // Show loading
    document.getElementById('paymentMethods').classList.add('hidden');
    document.getElementById('paymentLoading').classList.remove('hidden');

    // Create payment
    fetch('{{ route("customer.duitku.create-payment", $invoice->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            payment_method: method
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.payment_url) {
            window.location.href = data.payment_url;
        } else {
            alert('Gagal membuat pembayaran: ' + (data.message || 'Unknown error'));
            document.getElementById('paymentMethods').classList.remove('hidden');
            document.getElementById('paymentLoading').classList.add('hidden');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan. Silakan coba lagi.');
        document.getElementById('paymentMethods').classList.remove('hidden');
        document.getElementById('paymentLoading').classList.add('hidden');
    });
}
</script>
@endsection
