<?php

namespace App\Exports;

use App\Models\AdminWalletTransaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WalletTransactionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    protected $transactions;
    protected $runningBalance = 0;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'Tanggal & Waktu',
            'Jenis Transaksi',
            'Keterangan',
            'Nominal Masuk',
            'Nominal Keluar',
            'Saldo Dompet'
        ];
    }

    public function map($transaction): array
    {
        $typeLabel = $transaction->type === 'payment_credit' ? 'Uang Masuk' : 'Uang Keluar';
        
        $masuk = $transaction->type === 'payment_credit' ? $transaction->amount : 0;
        $keluar = $transaction->type !== 'payment_credit' ? $transaction->amount : 0;

        $this->runningBalance = $this->runningBalance + $masuk - $keluar;

        return [
            $transaction->created_at->format('Y-m-d H:i:s'),
            $typeLabel,
            $transaction->description,
            $masuk,
            $keluar,
            $this->runningBalance
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '"Rp"#,##0', // Format Rupiah
            'E' => '"Rp"#,##0',
            'F' => '"Rp"#,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1    => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '10b981', // Tailwind Emerald 500
                ]
            ]],
        ];
    }
}
