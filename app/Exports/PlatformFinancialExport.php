<?php

namespace App\Exports;

use App\Models\Invoice;
use App\Models\WithdrawalRequest;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PlatformFinancialExport implements WithMultipleSheets
{
    use Exportable;

    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new PlatformInvoicesSheet($this->month, $this->year);
        $sheets[] = new PlatformWithdrawalsSheet($this->month, $this->year);

        return $sheets;
    }
}
