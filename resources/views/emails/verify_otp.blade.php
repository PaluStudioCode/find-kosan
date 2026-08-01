<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); overflow: hidden; border: 1px solid #f1f5f9; }
        .header { background-color: #0f766e; padding: 40px 20px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 28px; letter-spacing: 1px; font-weight: 800; }
        .content { padding: 40px 30px; color: #334155; line-height: 1.7; font-size: 15px; }
        .content h2 { color: #0f172a; margin-top: 0; font-size: 22px; font-weight: 700; margin-bottom: 20px; }
        .otp-box { text-align: center; margin: 35px 0; background-color: #f1f5f9; padding: 20px; border-radius: 12px; border: 2px dashed #cbd5e1; }
        .otp-code { font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #0f766e; font-family: monospace; margin: 0; }
        .footer { background-color: #f8fafc; padding: 25px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div style="background-color: #f8fafc; padding: 20px;">
        <div class="container">
            <div class="header">
                <h1>CARI KOSAN</h1>
            </div>
            <div class="content">
                <h2>Halo, {{ $notifiable->name }}! 👋</h2>
                <p>Kami menerima permintaan untuk memverifikasi alamat email akun Anda di <strong>Cari Kosan</strong>.</p>
                <p>Silakan masukkan 6 digit kode rahasia (OTP) di bawah ini pada halaman verifikasi:</p>
                
                <div class="otp-box">
                    <p class="otp-code">{{ $otp }}</p>
                </div>
                
                <p style="color: #64748b; font-size: 14px; text-align: center;">Kode OTP ini hanya berlaku selama <strong>5 menit</strong>.</p>
                
                <hr style="border: none; border-top: 1px dashed #cbd5e1; margin: 35px 0;">
                
                <p style="color: #64748b; font-size: 14px; margin-bottom: 5px;">
                    <strong>Perhatian:</strong> Jangan berikan kode OTP ini kepada siapa pun, termasuk pihak yang mengaku sebagai admin Cari Kosan. 
                    Jika Anda tidak merasa melakukan permintaan verifikasi, Anda dapat mengabaikan email ini.
                </p>
            </div>
            <div class="footer">
                <p style="margin: 0;">&copy; {{ date('Y') }} <strong>Cari Kosan</strong>. Hak cipta dilindungi undang-undang.</p>
                <p style="margin: 5px 0 0 0;">Platform Pencarian Kos Modern & Terpercaya</p>
            </div>
        </div>
    </div>
</body>
</html>
