<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Pesanan - {{ companyName() }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="bg-gradient-to-r from-slate-900 to-cyan-900 text-white py-4">
        <div class="container mx-auto px-4">
            <a href="{{ url('/') }}" class="flex items-center space-x-2">
                <i class="fas fa-wifi text-2xl text-cyan-400"></i>
                <span class="text-xl font-bold">{{ companyName() }}</span>
            </a>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Search Form -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-search text-cyan-600 mr-2"></i>Lacak Pesanan
                </h1>
                <form method="GET" class="flex gap-3">
                    <input type="text" name="order_number" value="{{ request('order_number') }}" placeholder="Masukkan No. Pesanan atau No. HP" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500">
                    <button type="submit" class="bg-cyan-600 text-white px-6 py-3 rounded-lg hover:bg-cyan-700 transition">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            @if($order)
            <!-- Order Details -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm opacity-80">No. Pesanan</p>
                            <p class="text-2xl font-bold">{{ $order->order_number }}</p>
                        </div>
                        <div class="text-right">
                            @php $badge = $order->status_badge; @endphp
                            <span class="px-3 py-1 bg-white/20 rounded-full text-sm">{{ $badge['label'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Timeline -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-800 mb-4">Status Pesanan</h3>
                        <div class="relative">
                            @php
                                $statuses = ['pending', 'confirmed', 'scheduled', 'installing', 'completed'];
                                $currentIndex = array_search($order->status, $statuses);
                                if ($order->status === 'cancelled') $currentIndex = -1;
                            @endphp
                            <div class="flex justify-between">
                                @foreach(['Pending', 'Dikonfirmasi', 'Dijadwalkan', 'Pemasangan', 'Selesai'] as $index => $label)
                                <div class="flex flex-col items-center flex-1">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $index <= $currentIndex ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400' }}">
                                        @if($index < $currentIndex)
                                            <i class="fas fa-check text-sm"></i>
                                        @elseif($index === $currentIndex)
                                            <i class="fas fa-circle text-xs"></i>
                                        @else
                                            <span class="text-xs">{{ $index + 1 }}</span>
                                        @endif
                                    </div>
                                    <p class="text-xs mt-1 text-center {{ $index <= $currentIndex ? 'text-green-600 font-medium' : 'text-gray-400' }}">{{ $label }}</p>
                                </div>
                                @endforeach
                            </div>
                            <div class="absolute top-4 left-0 right-0 h-0.5 bg-gray-200 -z-10">
                                <div class="h-full bg-green-500 transition-all" style="width: {{ $currentIndex >= 0 ? ($currentIndex / 4 * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>

                    @if($order->status === 'cancelled')
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <p class="text-red-800 font-medium"><i class="fas fa-times-circle mr-2"></i>Pesanan Dibatalkan</p>
                    </div>
                    @endif

                    <!-- Order Info -->
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Nama</p>
                            <p class="font-medium">{{ $order->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Telepon</p>
                            <p class="font-medium">{{ $order->customer_phone }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Paket</p>
                            <p class="font-medium">{{ $order->package->name }} ({{ $order->package->speed }})</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Tipe Koneksi</p>
                            <p class="font-medium">{{ strtoupper($order->connection_type) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Total Pembayaran</p>
                            <p class="font-bold text-cyan-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Status Pembayaran</p>
                            @php $payBadge = $order->payment_status_badge; @endphp
                            <span class="px-2 py-1 text-xs rounded-full bg-{{ $payBadge['color'] }}-100 text-{{ $payBadge['color'] }}-800">{{ $payBadge['label'] }}</span>
                        </div>
                        @if($order->installation_date)
                        <div>
                            <p class="text-gray-500">Jadwal Pemasangan</p>
                            <p class="font-medium">{{ $order->installation_date->format('d M Y') }} {{ $order->installation_time }}</p>
                        </div>
                        @endif
                        @if($order->technician)
                        <div>
                            <p class="text-gray-500">Teknisi</p>
                            <p class="font-medium">{{ $order->technician->name }}</p>
                        </div>
                        @endif
                    </div>

                    <div class="mt-4 pt-4 border-t">
                        <p class="text-gray-500 text-sm">Alamat Pemasangan</p>
                        <p class="font-medium">{{ $order->customer_address }}</p>
                    </div>

                    @if($order->payment_status === 'pending' && $order->payment_url)
                    <div class="mt-6">
                        <a href="{{ $order->payment_url }}" class="block w-full bg-cyan-600 text-white py-3 rounded-lg font-semibold text-center hover:bg-cyan-700 transition">
                            <i class="fas fa-credit-card mr-2"></i>Bayar Sekarang
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @elseif(request('order_number'))
            <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                <i class="fas fa-search text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500">Pesanan tidak ditemukan</p>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
