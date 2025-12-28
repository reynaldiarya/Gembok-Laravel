@extends('layouts.app')

@section('title', 'Invoice Details')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <div class="mb-6">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.invoices.index') }}" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Invoice Details</h1>
                        <p class="text-gray-600 mt-1">{{ $invoice->invoice_number }}</p>
                    </div>
                </div>
            </div>

            <div class="max-w-4xl">
                <div class="bg-white rounded-xl shadow-md p-8">
                    <!-- Invoice Header -->
                    <div class="flex justify-between items-start mb-8 pb-6 border-b">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">{{ companyName() }}</h2>
                            <p class="text-gray-600 mt-2">ISP Management System</p>
                        </div>
                        <div class="text-right">
                            <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ strtoupper($invoice->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Invoice Info -->
                    <div class="grid grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-600 mb-3">BILL TO:</h3>
                            <p class="font-bold text-gray-900">{{ $invoice->customer->name }}</p>
                            <p class="text-gray-600">{{ $invoice->customer->phone }}</p>
                            <p class="text-gray-600">{{ $invoice->customer->email }}</p>
                            <p class="text-gray-600 mt-2">{{ $invoice->customer->address }}</p>
                        </div>
                        <div class="text-right">
                            <div class="mb-4">
                                <p class="text-sm text-gray-600">Invoice Number</p>
                                <p class="font-mono font-bold text-gray-900">{{ $invoice->invoice_number }}</p>
                            </div>
                            <div class="mb-4">
                                <p class="text-sm text-gray-600">Invoice Date</p>
                                <p class="font-medium text-gray-900">{{ $invoice->created_at->format('d M Y') }}</p>
                            </div>
                            @if($invoice->due_date)
                            <div class="mb-4">
                                <p class="text-sm text-gray-600">Due Date</p>
                                <p class="font-medium text-gray-900">{{ $invoice->due_date->format('d M Y') }}</p>
                            </div>
                            @endif
                            @if($invoice->paid_date)
                            <div>
                                <p class="text-sm text-gray-600">Paid Date</p>
                                <p class="font-medium text-green-600">{{ $invoice->paid_date->format('d M Y') }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Invoice Items -->
                    <div class="mb-8">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Description</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-4">
                                        <p class="font-medium text-gray-900">{{ $invoice->package->name ?? 'Service' }}</p>
                                        <p class="text-sm text-gray-600">{{ ucfirst($invoice->invoice_type) }} - {{ $invoice->description ?? 'Monthly subscription' }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-right font-medium text-gray-900">
                                        Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @if($invoice->tax_amount > 0)
                                <tr>
                                    <td class="px-4 py-4 text-right font-medium text-gray-600">Tax</td>
                                    <td class="px-4 py-4 text-right font-medium text-gray-900">
                                        Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td class="px-4 py-4 text-right font-bold text-gray-900">TOTAL</td>
                                    <td class="px-4 py-4 text-right font-bold text-2xl text-gray-900">
                                        Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 pt-6 border-t">
                        @if($invoice->status === 'unpaid')
                            @php $duitkuEnabled = \App\Models\AppSetting::getValue('duitku_enabled', 'false') === 'true'; @endphp
                            @if($duitkuEnabled)
                            <button type="button" onclick="openPaymentModal()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-credit-card mr-2"></i>Bayar Online
                            </button>
                            @endif
                            <form action="{{ route('admin.invoices.pay', $invoice) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                                    <i class="fas fa-check-circle mr-2"></i>Mark as Paid
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.invoices.print', $invoice) }}" target="_blank" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition">
                            <i class="fas fa-print mr-2"></i>Print Invoice
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
@if($invoice->status === 'unpaid')
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b bg-gradient-to-r from-blue-600 to-indigo-600 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-credit-card text-white text-2xl mr-3"></i>
                    <div>
                        <h3 class="text-xl font-bold text-white">Pilih Metode Pembayaran</h3>
                        <p class="text-blue-100 text-sm">Invoice: {{ $invoice->invoice_number }}</p>
                    </div>
                </div>
                <button onclick="closePaymentModal()" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total Pembayaran</span>
                    <span class="text-2xl font-bold text-gray-900">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="space-y-3" id="paymentMethods">
                <!-- QRIS -->
                <div class="payment-method-group">
                    <h4 class="text-sm font-semibold text-gray-500 mb-2">QRIS (Semua E-Wallet)</h4>
                    <button type="button" onclick="selectPayment('SP')" class="payment-btn w-full flex items-center p-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-qrcode text-purple-600 text-xl"></i>
                        </div>
                        <div class="text-left flex-1">
                            <p class="font-semibold text-gray-900">QRIS</p>
                            <p class="text-sm text-gray-500">Scan dengan semua e-wallet</p>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>
                </div>

                <!-- Virtual Account -->
                <div class="payment-method-group">
                    <h4 class="text-sm font-semibold text-gray-500 mb-2">Virtual Account</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="selectPayment('BC')" class="payment-btn flex items-center p-3 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <span class="text-blue-600 font-bold text-xs">BCA</span>
                            </div>
                            <span class="font-medium text-gray-900 text-sm">BCA VA</span>
                        </button>
                        <button type="button" onclick="selectPayment('M2')" class="payment-btn flex items-center p-3 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                                <span class="text-yellow-600 font-bold text-xs">MDR</span>
                            </div>
                            <span class="font-medium text-gray-900 text-sm">Mandiri VA</span>
                        </button>
                        <button type="button" onclick="selectPayment('I1')" class="payment-btn flex items-center p-3 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                                <span class="text-orange-600 font-bold text-xs">BNI</span>
                            </div>
                            <span class="font-medium text-gray-900 text-sm">BNI VA</span>
                        </button>
                        <button type="button" onclick="selectPayment('BR')" class="payment-btn flex items-center p-3 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <span class="text-blue-600 font-bold text-xs">BRI</span>
                            </div>
                            <span class="font-medium text-gray-900 text-sm">BRI VA</span>
                        </button>
                        <button type="button" onclick="selectPayment('BT')" class="payment-btn flex items-center p-3 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                                <span class="text-red-600 font-bold text-xs">PMT</span>
                            </div>
                            <span class="font-medium text-gray-900 text-sm">Permata VA</span>
                        </button>
                        <button type="button" onclick="selectPayment('B1')" class="payment-btn flex items-center p-3 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                                <span class="text-red-600 font-bold text-xs">CIMB</span>
                            </div>
                            <span class="font-medium text-gray-900 text-sm">CIMB VA</span>
                        </button>
                    </div>
                </div>

                <!-- E-Wallet -->
                <div class="payment-method-group">
                    <h4 class="text-sm font-semibold text-gray-500 mb-2">E-Wallet</h4>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="selectPayment('OV')" class="payment-btn flex flex-col items-center p-3 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-2">
                                <span class="text-purple-600 font-bold text-xs">OVO</span>
                            </div>
                            <span class="font-medium text-gray-900 text-xs">OVO</span>
                        </button>
                        <button type="button" onclick="selectPayment('DA')" class="payment-btn flex flex-col items-center p-3 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-2">
                                <span class="text-blue-600 font-bold text-xs">DANA</span>
                            </div>
                            <span class="font-medium text-gray-900 text-xs">DANA</span>
                        </button>
                        <button type="button" onclick="selectPayment('SA')" class="payment-btn flex flex-col items-center p-3 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mb-2">
                                <span class="text-orange-600 font-bold text-xs">SPay</span>
                            </div>
                            <span class="font-medium text-gray-900 text-xs">ShopeePay</span>
                        </button>
                    </div>
                </div>

                <!-- Retail -->
                <div class="payment-method-group">
                    <h4 class="text-sm font-semibold text-gray-500 mb-2">Retail / Minimarket</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="selectPayment('IR')" class="payment-btn flex items-center p-3 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-store text-red-600"></i>
                            </div>
                            <span class="font-medium text-gray-900 text-sm">Indomaret</span>
                        </button>
                        <button type="button" onclick="selectPayment('A1')" class="payment-btn flex items-center p-3 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-store text-blue-600"></i>
                            </div>
                            <span class="font-medium text-gray-900 text-sm">Alfamart</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div id="paymentLoading" class="hidden text-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                <p class="text-gray-600">Memproses pembayaran...</p>
            </div>
        </div>
    </div>
</div>
@endif

<script>
function openPaymentModal() {
    document.getElementById('paymentModal').classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function selectPayment(method) {
    // Show loading
    document.getElementById('paymentMethods').classList.add('hidden');
    document.getElementById('paymentLoading').classList.remove('hidden');

    // Create payment
    fetch('{{ route("admin.duitku.create-payment", $invoice->id) }}', {
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
            // Redirect to payment page
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

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePaymentModal();
    }
});

// Close modal on backdrop click
document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});
</script>
@endsection
