<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order {{ $package->name }} - {{ companyName() }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <div class="bg-gradient-to-r from-slate-900 to-cyan-900 text-white py-4">
        <div class="container mx-auto px-4">
            <a href="{{ url('/') }}" class="flex items-center space-x-2">
                <i class="fas fa-wifi text-2xl text-cyan-400"></i>
                <span class="text-xl font-bold">{{ companyName() }}</span>
            </a>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Order Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h1 class="text-2xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-shopping-cart text-cyan-600 mr-2"></i>Form Pendaftaran
                        </h1>

                        <form action="{{ route('order.store') }}" method="POST" id="orderForm">
                            @csrf
                            <input type="hidden" name="package_id" value="{{ $package->id }}">

                            <!-- Personal Info -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">
                                    <i class="fas fa-user mr-2 text-cyan-600"></i>Data Diri
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap *</label>
                                        <input type="text" name="customer_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" placeholder="Nama lengkap sesuai KTP">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">No. WhatsApp *</label>
                                        <input type="text" name="customer_phone" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" placeholder="08xxxxxxxxxx">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                        <input type="email" name="customer_email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" placeholder="email@example.com">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">No. KTP</label>
                                        <input type="text" name="customer_nik" maxlength="16" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" placeholder="16 digit NIK">
                                    </div>
                                </div>
                            </div>

                            <!-- Address -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">
                                    <i class="fas fa-map-marker-alt mr-2 text-cyan-600"></i>Alamat Pemasangan
                                </h3>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap *</label>
                                    <textarea name="customer_address" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota"></textarea>
                                </div>
                                <input type="hidden" name="latitude" id="latitude">
                                <input type="hidden" name="longitude" id="longitude">
                                <button type="button" onclick="getLocation()" class="mt-2 text-sm text-cyan-600 hover:text-cyan-800">
                                    <i class="fas fa-crosshairs mr-1"></i>Ambil Lokasi GPS
                                </button>
                                <span id="locationStatus" class="text-sm text-gray-500 ml-2"></span>
                            </div>

                            <!-- Connection Type -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">
                                    <i class="fas fa-plug mr-2 text-cyan-600"></i>Tipe Koneksi
                                </h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:border-cyan-500 transition" id="pppoe-label">
                                        <input type="radio" name="connection_type" value="pppoe" checked class="hidden" onchange="updateConnectionType()">
                                        <div class="flex items-center">
                                            <i class="fas fa-ethernet text-2xl text-cyan-600 mr-3"></i>
                                            <div>
                                                <p class="font-semibold">PPPoE</p>
                                                <p class="text-xs text-gray-500">Koneksi kabel fiber</p>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:border-cyan-500 transition" id="hotspot-label">
                                        <input type="radio" name="connection_type" value="hotspot" class="hidden" onchange="updateConnectionType()">
                                        <div class="flex items-center">
                                            <i class="fas fa-wifi text-2xl text-purple-600 mr-3"></i>
                                            <div>
                                                <p class="font-semibold">Hotspot</p>
                                                <p class="text-xs text-gray-500">Koneksi wireless</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">
                                    <i class="fas fa-credit-card mr-2 text-cyan-600"></i>Metode Pembayaran
                                </h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:border-cyan-500 transition" id="midtrans-label">
                                        <input type="radio" name="payment_method" value="midtrans" checked class="hidden" onchange="updatePaymentMethod()">
                                        <div class="flex items-center">
                                            <i class="fas fa-globe text-2xl text-blue-600 mr-3"></i>
                                            <div>
                                                <p class="font-semibold">Bayar Online</p>
                                                <p class="text-xs text-gray-500">VA, QRIS, E-Wallet</p>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:border-cyan-500 transition" id="manual-label">
                                        <input type="radio" name="payment_method" value="manual" class="hidden" onchange="updatePaymentMethod()">
                                        <div class="flex items-center">
                                            <i class="fas fa-money-bill text-2xl text-green-600 mr-3"></i>
                                            <div>
                                                <p class="font-semibold">Transfer Manual</p>
                                                <p class="text-xs text-gray-500">Konfirmasi admin</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                                <textarea name="customer_notes" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" placeholder="Catatan tambahan untuk pemasangan..."></textarea>
                            </div>

                            <button type="submit" class="w-full bg-gradient-to-r from-cyan-600 to-blue-600 text-white py-3 rounded-lg font-semibold hover:from-cyan-700 hover:to-blue-700 transition">
                                <i class="fas fa-paper-plane mr-2"></i>Kirim Pesanan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-lg p-6 sticky top-4">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Ringkasan Pesanan</h3>
                        
                        <div class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-lg p-4 mb-4">
                            <p class="text-sm opacity-80">Paket Dipilih</p>
                            <p class="text-xl font-bold">{{ $package->name }}</p>
                            <p class="text-sm">{{ $package->speed }}</p>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Biaya Paket/bulan</span>
                                <span class="font-medium">Rp {{ number_format($package->price, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Biaya Pemasangan</span>
                                <span class="font-medium">Rp 150.000</span>
                            </div>
                            <hr>
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total Bayar</span>
                                <span class="text-cyan-600">Rp {{ number_format($package->price + 150000, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                            <p class="text-xs text-yellow-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                Pembayaran pertama mencakup biaya pemasangan + langganan bulan pertama.
                            </p>
                        </div>

                        <div class="mt-4 space-y-2 text-xs text-gray-500">
                            <p><i class="fas fa-check text-green-500 mr-1"></i> Gratis survei lokasi</p>
                            <p><i class="fas fa-check text-green-500 mr-1"></i> Garansi perangkat 1 tahun</p>
                            <p><i class="fas fa-check text-green-500 mr-1"></i> Support 24/7</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function updateConnectionType() {
        document.querySelectorAll('[name="connection_type"]').forEach(radio => {
            const label = document.getElementById(radio.value + '-label');
            if (radio.checked) {
                label.classList.add('border-cyan-500', 'bg-cyan-50');
            } else {
                label.classList.remove('border-cyan-500', 'bg-cyan-50');
            }
        });
    }

    function updatePaymentMethod() {
        document.querySelectorAll('[name="payment_method"]').forEach(radio => {
            const label = document.getElementById(radio.value + '-label');
            if (radio.checked) {
                label.classList.add('border-cyan-500', 'bg-cyan-50');
            } else {
                label.classList.remove('border-cyan-500', 'bg-cyan-50');
            }
        });
    }

    function getLocation() {
        const status = document.getElementById('locationStatus');
        status.textContent = 'Mengambil lokasi...';
        
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                    status.innerHTML = '<i class="fas fa-check text-green-500"></i> Lokasi berhasil diambil';
                },
                (error) => {
                    status.innerHTML = '<i class="fas fa-times text-red-500"></i> Gagal mengambil lokasi';
                }
            );
        } else {
            status.textContent = 'Browser tidak mendukung GPS';
        }
    }

    // Initialize
    updateConnectionType();
    updatePaymentMethod();
    </script>
</body>
</html>
