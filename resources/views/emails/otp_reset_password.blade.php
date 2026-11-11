<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Reset Kata Sandi</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 520px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #0f172a;
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 4px 0 0;
            font-size: 12px;
            color: #94a3b8;
        }
        .content {
            padding: 32px 24px;
        }
        .greeting {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .instruction {
            font-size: 13px;
            line-height: 1.6;
            color: #475569;
            margin-bottom: 24px;
        }
        .otp-box {
            background-color: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            margin-bottom: 24px;
        }
        .otp-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .otp-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #0284c7;
        }
        .warning {
            font-size: 12px;
            color: #64748b;
            background-color: #fffbeb;
            border: 1px solid #fef3c7;
            border-radius: 8px;
            padding: 12px;
            line-height: 1.5;
        }
        .footer {
            background-color: #f8fafc;
            padding: 16px 24px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SMK NEGERI 1 BANTUL</h1>
            <p>Sistem Presensi & Agenda Pembelajaran School Class Progress</p>
        </div>

        <div class="content">
            <div class="greeting">Halo, {{ $user->fullname ?? $user->username }}!</div>
            <div class="instruction">
                Kami menerima permintaan untuk mereset kata sandi akun Anda. Gunakan kode verifikasi One-Time Password (OTP) 6 digit di bawah ini untuk melanjutkan penggantian kata sandi:
            </div>

            <div class="otp-box">
                <div class="otp-label">Kode Verifikasi OTP Anda</div>
                <div class="otp-code">{{ $otpCode }}</div>
            </div>

            <div class="warning">
                <strong>Peringatan Keamanan:</strong> Kode ini berlaku selama <strong>10 menit</strong>. Jangan pernah memberikan atau memberitahukan kode verifikasi ini kepada siapapun demi keamanan akun sekolah Anda.
            </div>
        </div>

        <div class="footer">
            &copy; 2026 SMK Negeri 1 Bantul &bull; School Class Progress RPL<br>
            Email otomatis, mohon untuk tidak membalas pesan ini.
        </div>
    </div>
</body>
</html>

