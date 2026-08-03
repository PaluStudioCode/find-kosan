<?php

namespace App\Exports;

use App\Models\WithdrawalRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlatformWithdrawalsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
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
        return WithdrawalRequest::with(['admin'])
            ->where('status', 'selesai')
            ->whereMonth('transferred_at', $this->month)
            ->whereYear('transferred_at', $this->year)
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID Penarikan',
            'Tanggal Transfer',
            'Pemilik Kos',
            'Bank Tujuan',
            'Nomor Rekening',
            'Atas Nama',
            'Nominal Tarik Kotor',
            'Potongan PPh',
            'Nominal Ditransfer Bersih',
            'No. Referensi Transfer'
        ];
    }

    public function map($withdrawal): array
    {
        return [
            'WD-'.$withdrawal->id,
            $withdrawal->transferred_at->format('d/m/Y H:i'),
            $withdrawal->admin->name ?? 'Admin Terhapus',
            $withdrawal->bank_name,
            $withdrawal->account_number,
            $withdrawal->account_holder_name,
            $withdrawal->amount,
            $withdrawal->pph_amount,
            $withdrawal->net_amount,
            $withdrawal->transfer_reference,
        ];
    }

    public function title(): string
    {
        return 'Pencairan Dana (Payouts)';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
}
