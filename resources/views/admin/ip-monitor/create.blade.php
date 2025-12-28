@extends('layouts.app')

@section('title', 'Tambah IP Monitor')

@section('content')
<div class="flex h-screen bg-gray-900">
    @include('admin.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
        @include('admin.partials.topbar')

        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-2xl mx-auto">
                <!-- Header -->
                <div class="flex items-center mb-6">
                    <a href="{{ route('admin.ip-monitor.index') }}" class="text-gray-400 hover:text-white mr-4">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Tambah IP Monitor</h1>
                        <p class="text-gray-400">Tambah IP baru untuk dimonitor</p>
                    </div>
                </div>

                <!-- Form -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <form action="{{ route('admin.ip-monitor.store') }}" method="POST">
                        @csrf
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">IP Address *</label>
                                <input type="text" name="ip_address" value="{{ old('ip_address') }}" required
                                       class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-cyan-500"
                                       placeholder="192.168.1.1">
                                @error('ip_address')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Nama / Label</label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                       class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-cyan-500"
                                       placeholder="Router Utama">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Customer (Opsional)</label>
                                <select name="customer_id" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-cyan-500">
                                    <option value="">-- Pilih Customer --</option>
                                    @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }} - {{ $customer->pppoe_username }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Network Device (Opsional)</label>
                                <select name="network_device_id" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-cyan-500">
                                    <option value="">-- Pilih Device --</option>
                                    @foreach($devices as $device)
                                    <option value="{{ $device->id }}" {{ old('network_device_id') == $device->id ? 'selected' : '' }}>
                                        {{ $device->name }} ({{ $device->host }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Check Interval (detik) *</label>
                                    <input type="number" name="check_interval" value="{{ old('check_interval', 300) }}" required min="60" max="3600"
                                           class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-cyan-500">
                                    <p class="text-gray-500 text-xs mt-1">Min: 60, Max: 3600</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Alert Threshold *</label>
                                    <input type="number" name="alert_threshold" value="{{ old('alert_threshold', 3) }}" required min="1" max="10"
                                           class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-cyan-500">
                                    <p class="text-gray-500 text-xs mt-1">Jumlah gagal berturut-turut sebelum alert</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-6">
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                           class="w-4 h-4 text-cyan-600 bg-gray-700 border-gray-600 rounded focus:ring-cyan-500">
                                    <span class="ml-2 text-gray-300">Aktif</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="alert_enabled" value="1" {{ old('alert_enabled', true) ? 'checked' : '' }}
                                           class="w-4 h-4 text-cyan-600 bg-gray-700 border-gray-600 rounded focus:ring-cyan-500">
                                    <span class="ml-2 text-gray-300">Kirim Alert (WhatsApp)</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-700">
                            <a href="{{ route('admin.ip-monitor.index') }}" class="px-6 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition">
                                Batal
                            </a>
                            <button type="submit" class="px-6 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition">
                                <i class="fas fa-save mr-2"></i>Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
