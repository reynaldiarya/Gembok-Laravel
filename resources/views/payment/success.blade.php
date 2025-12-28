<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - {{ companyName() }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: pulse 2s infinite;
        }
        .success-icon i {
            font-size: 50px;
            color: white;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .checkmark {
            animation: checkmark 0.5s ease-in-out;
        }
        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check checkmark"></i>
        </div>
        <h2 class="text-success mb-3">Pembayaran Berhasil!</h2>
        <p class="text-muted mb-4">
            Terima kasih! Pembayaran Anda telah berhasil diproses. 
            Anda akan menerima konfirmasi melalui WhatsApp.
        </p>
        
        <div class="bg-light rounded p-3 mb-4">
            <div class="row text-start">
                <div class="col-6">
                    <small class="text-muted">Status</small>
                    <p class="mb-0 fw-bold text-success">Lunas</p>
                </div>
                <div class="col-6">
                    <small class="text-muted">Tanggal</small>
                    <p class="mb-0 fw-bold">{{ now()->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="fas fa-home me-2"></i>Kembali ke Beranda
            </a>
            <a href="{{ url('/customer/dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-user me-2"></i>Dashboard Pelanggan
            </a>
        </div>
    </div>
</body>
</html>
