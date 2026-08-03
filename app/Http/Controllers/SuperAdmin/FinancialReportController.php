<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Exports\PlatformFinancialExport;
use App\Http\Controllers\Controller;
use App\Models\AdminWalletTransaction;
use App\Models\Invoice;
use App\Models\WithdrawalRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        return Inertia::render('SuperAdmin/FinancialReports/Index', [
            'filters' => ['month' => $month, 'year' => $year],
            'summary' => Inertia::defer(function () use ($month, $year) {
                // Total GTV (Uang Masuk dari Penyewa)
                $gtv = Invoice::where('status', 'lunas')
                    ->whereMonth('updated_at', $month)
                    ->whereYear('updated_at', $year)
                    ->sum('amount');

                // Total PPN
                $ppn = Invoice::where('status', 'lunas')
                    ->whereMonth('updated_at', $month)
                    ->whereYear('updated_at', $year)
                    ->sum('ppn_amount');

                // Total PPh
                $pph = WithdrawalRequest::where('status', 'selesai')
                    ->whereMonth('transferred_at', $month)
                    ->whereYear('transferred_at', $year)
                    ->sum('pph_amount');

                // Total Pencairan Dana
                $payouts = WithdrawalRequest::where('status', 'selesai')
                    ->whereMonth('transferred_at', $month)
                    ->whereYear('transferred_at', $year)
                    ->sum('net_amount');

                return [
                    'gtv' => (float) $gtv,
                    'ppn' => (float) $ppn,
                    'pph' => (float) $pph,
                    'payouts' => (float) $payouts,
                    'net_income' => (float) ($ppn + $pph), // Total uang pajak (negara)
                ];
            }),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $fileName = "Laporan_Keuangan_Platform_{$year}_{$month}.xlsx";

        return Excel::download(new PlatformFinancialExport($month, $year), $fileName);
    }

    public function exportPdf(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $date = Carbon::createFromDate($year, $month, 1);
        $periodName = $date->translatedFormat('F Y');

        // Total GTV
        $gtv = Invoice::where('status', 'lunas')
            ->whereMonth('updated_at', $month)
            ->whereYear('updated_at', $year)
            ->sum('amount');

        // Total PPN
        $ppn = Invoice::where('status', 'lunas')
            ->whereMonth('updated_at', $month)
            ->whereYear('updated_at', $year)
            ->sum('ppn_amount');

        // Total PPh
        $pph = WithdrawalRequest::where('status', 'selesai')
            ->whereMonth('transferred_at', $month)
            ->whereYear('transferred_at', $year)
            ->sum('pph_amount');

        // Total Pencairan Dana
        $payouts = WithdrawalRequest::where('status', 'selesai')
            ->whereMonth('transferred_at', $month)
            ->whereYear('transferred_at', $year)
            ->sum('net_amount');

        $data = [
            'periodName' => $periodName,
            'gtv' => $gtv,
            'ppn' => $ppn,
            'pph' => $pph,
            'payouts' => $payouts,
            'netIncome' => $ppn + $pph,
            'printDate' => now()->translatedFormat('d F Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.superadmin-financial-summary', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("Ringkasan_Keuangan_Platform_{$year}_{$month}.pdf");
    }
}
