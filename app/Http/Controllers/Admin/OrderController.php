<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Technician;
use App\Services\MikrotikService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['package', 'technician'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        $stats = [
            'pending' => Order::where('status', 'pending')->count(),
            'confirmed' => Order::where('status', 'confirmed')->count(),
            'installing' => Order::where('status', 'installing')->count(),
            'completed' => Order::where('status', 'completed')->count(),
        ];

        $technicians = Technician::where('is_active', true)->get();

        return view('admin.orders.index', compact('orders', 'stats', 'technicians'));
    }

    public function show(Order $order)
    {
        $order->load(['package', 'technician', 'customer']);
        $technicians = Technician::where('is_active', true)->get();
        
        return view('admin.orders.show', compact('order', 'technicians'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,scheduled,installing,completed,cancelled',
            'installation_date' => 'nullable|date',
            'installation_time' => 'nullable|string',
            'technician_id' => 'nullable|exists:technicians,id',
            'admin_notes' => 'nullable|string',
        ]);

        $oldStatus = $order->status;
        $order->update($validated);

        // Send notification to customer (async/lazy)
        if ($oldStatus !== $validated['status']) {
            try {
                $this->notifyCustomer($order, $validated['status']);
            } catch (\Exception $e) {
                \Log::warning('Order notification failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diupdate'
        ]);
    }

    public function confirmPayment(Order $order)
    {
        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        // Notify customer (lazy load WhatsApp service)
        try {
            $message = "✅ *Pembayaran Dikonfirmasi*\n\n";
            $message .= "Halo *{$order->customer_name}*,\n\n";
            $message .= "Pembayaran untuk pesanan *{$order->order_number}* telah dikonfirmasi.\n\n";
            $message .= "Tim kami akan segera menghubungi Anda untuk jadwal pemasangan.\n\n";
            $message .= "Terima kasih!\n*" . companyName() . "*";

            app(WhatsAppService::class)->send($order->customer_phone, $message);
        } catch (\Exception $e) {
            \Log::warning('WhatsApp notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran dikonfirmasi'
        ]);
    }

    public function complete(Order $order)
    {
        // Create customer account
        $customer = Customer::create([
            'name' => $order->customer_name,
            'phone' => $order->customer_phone,
            'email' => $order->customer_email,
            'address' => $order->customer_address,
            'package_id' => $order->package_id,
            'status' => 'active',
            'join_date' => now(),
            'latitude' => $order->latitude,
            'longitude' => $order->longitude,
        ]);

        // Generate PPPoE credentials if needed
        if ($order->connection_type === 'pppoe') {
            $username = $this->generatePPPoEUsername($customer);
            $password = $this->generatePassword();

            $customer->update([
                'pppoe_username' => $username,
                'pppoe_password' => $password,
            ]);

            // Create PPPoE secret in Mikrotik (lazy load)
            try {
                $mikrotik = app(MikrotikService::class);
                if ($mikrotik->isConnected()) {
                    $mikrotik->createPPPoESecret([
                        'username' => $username,
                        'password' => $password,
                        'profile' => $order->package->pppoe_profile ?? 'default',
                        'comment' => "Customer: {$customer->name}",
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('Mikrotik PPPoE creation failed: ' . $e->getMessage());
            }

            // Send credentials to customer
            try {
                $message = "🎉 *Selamat! Pemasangan Selesai*\n\n";
                $message .= "Halo *{$customer->name}*,\n\n";
                $message .= "Layanan internet Anda sudah aktif!\n\n";
                $message .= "📋 *Kredensial PPPoE:*\n";
                $message .= "👤 Username: `{$username}`\n";
                $message .= "🔑 Password: `{$password}`\n\n";
                $message .= "📦 Paket: {$order->package->name}\n";
                $message .= "⚡ Kecepatan: {$order->package->speed}\n\n";
                $message .= "Terima kasih telah berlangganan!\n*" . companyName() . "*";

                app(WhatsAppService::class)->send($customer->phone, $message);
            } catch (\Exception $e) {
                \Log::warning('WhatsApp credentials notification failed: ' . $e->getMessage());
            }
        }

        // Update order
        $order->update([
            'status' => 'completed',
            'customer_id' => $customer->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesanan selesai, pelanggan berhasil dibuat',
            'customer_id' => $customer->id
        ]);
    }

    protected function notifyCustomer(Order $order, $status)
    {
        $company = companyName();
        $messages = [
            'confirmed' => "✅ *Pesanan Dikonfirmasi*\n\nHalo *{$order->customer_name}*,\n\nPesanan Anda *{$order->order_number}* telah dikonfirmasi.\n\nKami akan segera menghubungi Anda untuk jadwal pemasangan.\n\n*{$company}*",
            
            'scheduled' => "📅 *Jadwal Pemasangan*\n\nHalo *{$order->customer_name}*,\n\nPemasangan dijadwalkan:\n📅 Tanggal: " . ($order->installation_date ? $order->installation_date->format('d M Y') : '-') . "\n⏰ Waktu: {$order->installation_time}\n\nTeknisi kami akan menghubungi Anda.\n\n*{$company}*",
            
            'installing' => "🔧 *Pemasangan Dimulai*\n\nHalo *{$order->customer_name}*,\n\nTeknisi kami sedang dalam perjalanan untuk melakukan pemasangan.\n\n*{$company}*",
            
            'cancelled' => "❌ *Pesanan Dibatalkan*\n\nHalo *{$order->customer_name}*,\n\nPesanan *{$order->order_number}* telah dibatalkan.\n\nJika ada pertanyaan, silakan hubungi kami.\n\n*{$company}*",
        ];

        if (isset($messages[$status])) {
            app(WhatsAppService::class)->send($order->customer_phone, $messages[$status]);
        }
    }

    protected function generatePPPoEUsername($customer)
    {
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $customer->name));
        $base = substr($base, 0, 10);
        return $base . $customer->id;
    }

    protected function generatePassword($length = 8)
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
    }
}
