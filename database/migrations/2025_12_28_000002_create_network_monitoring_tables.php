<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Network Devices for SNMP monitoring
        Schema::create('network_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('host'); // IP address
            $table->string('community')->default('public');
            $table->enum('type', ['router', 'switch', 'olt', 'server', 'ap', 'other'])->default('router');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('snmp_enabled')->default(true);
            $table->enum('status', ['online', 'offline', 'warning', 'unknown'])->default('unknown');
            $table->timestamp('last_check')->nullable();
            $table->integer('uptime_seconds')->nullable();
            $table->decimal('cpu_usage', 5, 2)->nullable();
            $table->decimal('memory_usage', 5, 2)->nullable();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamps();
        });

        // IP Monitors for static IP monitoring
        Schema::create('ip_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->string('name')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('network_device_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['up', 'down', 'unknown'])->default('unknown');
            $table->integer('latency_ms')->nullable(); // ping latency
            $table->decimal('packet_loss', 5, 2)->nullable();
            $table->timestamp('last_check')->nullable();
            $table->timestamp('last_up')->nullable();
            $table->timestamp('last_down')->nullable();
            $table->integer('check_interval')->default(300); // seconds
            $table->integer('alert_threshold')->default(3); // consecutive failures before alert
            $table->integer('consecutive_failures')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('alert_enabled')->default(true);
            $table->timestamps();
        });

        // IP Monitor Logs
        Schema::create('ip_monitor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ip_monitor_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['up', 'down']);
            $table->integer('latency_ms')->nullable();
            $table->decimal('packet_loss', 5, 2)->nullable();
            $table->timestamp('checked_at');
            $table->index(['ip_monitor_id', 'checked_at']);
        });

        // Network Device Alerts
        Schema::create('network_alerts', function (Blueprint $table) {
            $table->id();
            $table->morphs('alertable'); // network_device or ip_monitor
            $table->enum('type', ['down', 'up', 'warning', 'critical', 'recovery']);
            $table->string('title');
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_alerts');
        Schema::dropIfExists('ip_monitor_logs');
        Schema::dropIfExists('ip_monitors');
        Schema::dropIfExists('network_devices');
    }
};
