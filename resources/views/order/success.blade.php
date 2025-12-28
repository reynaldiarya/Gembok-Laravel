<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - {{ companyName() }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-lg mx-auto">
            <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check text-green-500 text-4xl"></i>
                </div>
                
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Pesanan Berhasil!</h1>
                <p class="text-gray-600 mb-6">Terima kasih telah mendaftar layanan kami</p>

                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-gray-500">No. Pesanan</p>
                            <p class="font-bold text-cyan-600">{{ $order->order_number }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Status</p>
                            <p class="font-medium">
                                @if($order->payment_status === 'paid')
                                    <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Lunas</span>
                                @else
                                    <span class="text-yellow-600"><i class="fas fa-clock mr-1"></i>Menunggu Pembayaran</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-500">Paket</p>
                            <p class="font-medium">{{ $order->package->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Total</p>
                            <p class="font-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                @if($order->payment_status !== 'paid' && $order->payment_method === 'manual')
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 text-left">
                    <h3 class="font-semibold text-yellow-800 mb-2">
                        <i class="fas fa-info-circle mr-1"></i>Instruksi Pembayaran
                    </h3>
                    <p class="text-sm text-yellow-700 mb-2">Silakan transfer ke rekening berikut:</p>
                    <div class="bg-white rounded p-3 text-sm">
                        <p><strong>Bank BCA</strong></p>
                        <p>No. Rek: 1234567890</p>
                        <p>A/N: {{ companyName() }}</p>
                        <p class="mt-2 font-bold">Jumlah: Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                    </div>
                    <p class="text-xs text-yellow-600 mt-2">
                        Setelah transfer, konfirmasi via WhatsApp dengan menyertakan bukti transfer.
                    </p>
                </div>
                @endif

                @if($order->payment_url && $order->payment_status === 'pending')
                <a href="{{ $order->payment_url }}" class="block w-full bg-cyan-600 text-white py-3 rounded-lg font-semibold hover:bg-cyan-700 transition mb-4">
                    <i class="fas fa-credit-card mr-2"></i>Bayar Sekarang
                </a>
                @endif

                <div class="space-y-3">
                    <a href="{{ route('order.track') }}?order_number={{ $order->order_number }}" class="block w-full bg-gray-100 text-gray-700 py-3 rounded-lg font-medium hover:bg-gray-200 transition">
                        <i class="fas fa-search mr-2"></i>Lacak Pesanan
                    </a>
                    <a href="{{ url('/') }}" class="block w-full text-cyan-600 py-2 hover:underline">
                        <i class="fas fa-home mr-2"></i>Kembali ke Beranda
                    </a>
                </div>

                <div class="mt-6 pt-6 border-t text-sm text-gray-500">
                    <p>Ada pertanyaan? Hubungi kami:</p>
                    <a href="https://wa.me/{{ config('services.whatsapp.admin_phone') }}" class="text-green-600 font-medium">
                        <i class="fab fa-whatsapp mr-1"></i>WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
