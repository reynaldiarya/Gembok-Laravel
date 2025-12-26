<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // OLT Devices
        Schema::create('olts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable(); // ZTE, Huawei, FiberHome, etc
            $table->string('model')->nullable(); // C300, MA5608T, etc
            $table->string('ip_address');
            $table->integer('snmp_port')->default(161);
            $table->string('snmp_community')->default('public');
            $table->string('snmp_version')->default('2c'); // 1, 2c, 3
            $table->string('telnet_username')->nullable();
            $table->string('telnet_password')->nullable();
            $table->integer('telnet_port')->default(23);
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->integer('total_pon_ports')->default(8);
            $table->integer('total_onus')->default(0);
            $table->integer('online_onus')->default(0);
            $table->integer('offline_onus')->default(0);
            $table->integer('los_onus')->default(0);
            $table->integer('dyinggasp_onus')->default(0);
            $table->string('uptime')->nullable();
            $table->enum('status', ['online', 'offline', 'maintenance'])->default('online');
            $table->timestamp('last_sync')->nullable();
            $table->timestamps();
        });

        // OLT PON Ports
        Schema::create('olt_pon_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained()->onDelete('cascade');
            $table->string('port_name'); // gpon-olt_1/1/1
            $table->integer('slot')->default(1);
            $table->integer('port')->default(1);
            $table->integer('total_onus')->default(0);
            $table->integer('online_onus')->default(0);
            $table->decimal('rx_power', 8, 2)->nullable(); // dBm
            $table->decimal('tx_power', 8, 2)->nullable(); // dBm
            $table->enum('status', ['up', 'down', 'admin_down'])->default('up');
            $table->timestamps();
        });

        // ONU Devices
        Schema::create('onus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained()->onDelete('cascade');
            $table->foreignId('pon_port_id')->nullable()->constrained('olt_pon_ports')->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('serial_number')->unique();
            $table->string('mac_address')->nullable();
            $table->string('name')->nullable();
            $table->string('model')->nullable(); // F660, HG8245H, etc
            $table->string('pon_location')->nullable(); // 1/1/1:1
            $table->integer('onu_id')->nullable(); // ONU ID on PON port
            $table->decimal('rx_power', 8, 2)->nullable(); // dBm
            $table->decimal('tx_power', 8, 2)->nullable(); // dBm
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('voltage', 5, 2)->nullable();
            $table->decimal('bias_current', 8, 2)->nullable();
            $table->bigInteger('rx_bytes')->default(0);
            $table->bigInteger('tx_bytes')->default(0);
            $table->string('firmware_version')->nullable();
            $table->string('hardware_version')->nullable();
            $table->string('ip_address')->nullable();
            $table->enum('status', ['online', 'offline', 'los', 'dyinggasp', 'unknown'])->default('unknown');
            $table->timestamp('last_online')->nullable();
            $table->timestamp('last_offline')->nullable();
            $table->text('offline_reason')->nullable();
            $table->timestamps();
            
            $table->index(['olt_id', 'status']);
            $table->index('serial_number');
        });

        // ONU Status History
        Schema::create('onu_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onu_id')->constrained()->onDelete('cascade');
            $table->enum('old_status', ['online', 'offline', 'los', 'dyinggasp', 'unknown'])->nullable();
            $table->enum('new_status', ['online', 'offline', 'los', 'dyinggasp', 'unknown']);
            $table->text('reason')->nullable();
            $table->timestamps();
            
            $table->index(['onu_id', 'created_at']);
        });

        // OLT Fans
        Schema::create('olt_fans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained()->onDelete('cascade');
            $table->string('fan_name'); // Fan 1, Fan 2
            $table->integer('fan_index')->default(1);
            $table->integer('speed_rpm')->default(0);
            $table->enum('speed_level', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['online', 'offline', 'warning'])->default('online');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olt_fans');
        Schema::dropIfExists('onu_status_logs');
        Schema::dropIfExists('onus');
        Schema::dropIfExists('olt_pon_ports');
        Schema::dropIfExists('olts');
    }
};
