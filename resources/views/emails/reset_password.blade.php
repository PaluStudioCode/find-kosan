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
        .button-wrap { text-align: center; margin: 35px 0; }
        .button { display: inline-block; padding: 14px 32px; background-color: #0d9488; color: #ffffff !important; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 6px -1px rgba(13, 148, 136, 0.2); transition: all 0.2s ease; }
        .button:hover { background-color: #0f766e; }
        .footer { background-color: #f8fafc; padding: 25px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0; }
        .link-raw { word-break: break-all; color: #0d9488; font-size: 13px; margin-top: 10px; display: block; text-decoration: none; }
        .link-raw:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div style="background-color: #f8fafc; padding: 20px;">
        <div class="container">
            <div class="header">
                <h1>CARI KOSAN</h1>
            </div>
            <div class="content">
                <h2>Halo, {{ $user->name }}! 👋</h2>
                <p>Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda di <strong>Cari Kosan</strong>.</p>
                <p>Jangan khawatir, cukup klik tombol di bawah ini untuk membuat kata sandi baru:</p>
                
                <div class="button-wrap">
                    <a href="{{ $url }}" class="button">Atur Ulang Kata Sandi</a>
                </div>
                
                <p style="color: #64748b; font-size: 14px;">Tautan atur ulang kata sandi ini akan kedaluwarsa dalam waktu <strong>60 menit</strong>.</p>
                <p style="color: #64748b; font-size: 14px;">Jika Anda tidak pernah meminta atur ulang kata sandi, abaikan email ini. Akun Anda tetap aman bersama kami.</p>
                
                <hr style="border: none; border-top: 1px dashed #cbd5e1; margin: 35px 0;">
                
                <p style="font-size: 13px; color: #94a3b8; margin-bottom: 5px;">
                    Jika Anda kesulitan mengklik tombol "Atur Ulang Kata Sandi", salin dan tempel URL di bawah ini ke browser Anda:
                </p>
                <a href="{{ $url }}" class="link-raw">{{ $url }}</a>
            </div>
            <div class="footer">
                <p style="margin: 0;">&copy; {{ date('Y') }} <strong>Cari Kosan</strong>. Hak cipta dilindungi undang-undang.</p>
                <p style="margin: 5px 0 0 0;">Platform Pencarian Kos Modern & Terpercaya</p>
            </div>
        </div>
    </div>
</body>
</html>
