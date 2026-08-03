<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlatformInvoicesSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        return Invoice::with(['user', 'admin'])
            ->where('status', 'lunas')
            ->whereMonth('updated_at', $this->month)
            ->whereYear('updated_at', $this->year)
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID Tagihan',
            'Tanggal Pembayaran',
            'Penyewa',
            'Pemilik Kos',
            'Harga Sewa',
            'PPN (Kompensasi)',
            'Total Bayar (GTV)'
        ];
    }

    public function map($invoice): array
    {
        return [
            'INV-'.$invoice->id,
            $invoice->updated_at->format('d/m/Y H:i'),
            $invoice->user->name ?? 'User Terhapus',
            $invoice->admin->name ?? 'Admin Terhapus',
            $invoice->rent_price,
            $invoice->ppn_amount,
            $invoice->amount,
        ];
    }

    public function title(): string
    {
        return 'Pendapatan Penyewaan';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
}
