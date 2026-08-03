<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ringkasan Keuangan Platform - {{ $periodName }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: #000; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 10px; border-bottom: 2px solid #000; }
        .header h1 { margin: 0; font-size: 20pt; text-transform: uppercase; letter-spacing: 2px; }
        .header p { margin: 5px 0 0 0; font-size: 11pt; color: #333; }
        
        .section { margin-bottom: 30px; }
        .section-title { font-size: 13pt; font-weight: bold; padding: 5px 0; border-bottom: 1px solid #666; margin-bottom: 15px; text-transform: uppercase; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #000; }
        th { background-color: #f2f2f2; text-align: left; padding: 8px; font-weight: bold; width: 60%; }
        td { padding: 8px; text-align: right; }
        
        .total-row th { background-color: #e6e6e6; }
        .total-row td { background-color: #e6e6e6; font-weight: bold; }
        
        .footer { margin-top: 50px; font-size: 10pt; color: #555; text-align: left; padding-top: 10px; font-style: italic; }
    </style>
</head>
<body>

    <div class="header">
        <h1>FIND KOSAN</h1>
        <p>Laporan Keuangan Eksekutif Platform</p>
        <p><strong>Periode: {{ $periodName }}</strong></p>
    </div>

    <div class="section">
        <div class="section-title">1. Ringkasan Pemasukan (Inflows)</div>
        <table>
            <tr>
                <th>Gross Transaction Value (GTV)<br><small style="font-weight:normal; color:#555;">Total kotor uang sewa yang masuk dari penyewa (Lunas)</small></th>
                <td>Rp {{ number_format($gtv, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Pajak Pertambahan Nilai (PPN)<br><small style="font-weight:normal; color:#555;">Total PPN yang dipungut dari penyewa</small></th>
                <td>Rp {{ number_format($ppn, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Ringkasan Pengeluaran (Outflows)</div>
        <table>
            <tr>
                <th>Pencairan Dana Pemilik (Payouts)<br><small style="font-weight:normal; color:#555;">Total dana bersih yang sukses ditransfer ke pemilik kos</small></th>
                <td>Rp {{ number_format($payouts, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Pajak Penghasilan (PPh)<br><small style="font-weight:normal; color:#555;">Total PPh yang dipotong dari pencairan dana pemilik kos</small></th>
                <td>Rp {{ number_format($pph, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">3. Kalkulasi Penerimaan Negara (Pajak)</div>
        <p style="font-size: 11pt; color: #555; margin-bottom: 10px;">Penerimaan total platform merupakan akumulasi dari PPN (dari penyewa) dan PPh (dari pemilik kos) yang ditahan oleh sistem untuk disetorkan ke kas negara.</p>
        <table>
            <tr>
                <th>Total PPN (Pajak Pertambahan Nilai)</th>
                <td>Rp {{ number_format($ppn, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Total PPh (Pajak Penghasilan) Ditahan</th>
                <td>Rp {{ number_format($pph, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <th>TOTAL PENERIMAAN PAJAK NEGARA</th>
                <td>Rp {{ number_format($netIncome, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Dicetak secara otomatis oleh Sistem Find Kosan pada {{ $printDate }}.<br>
        Dokumen ini sah dan di-*generate* berdasarkan data mutasi *database* terkini.
    </div>

</body>
</html>
