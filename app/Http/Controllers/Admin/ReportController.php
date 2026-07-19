<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with(['reporter', 'boardingHouse'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Reports/Index', [
            'reports' => $reports
        ]);
    }

    public function show(Report $report)
    {
        $report->load(['reporter', 'boardingHouse', 'handler']);
        return Inertia::render('Admin/Reports/Show', [
            'report' => $report
        ]);
    }

    public function update(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => 'required|in:menunggu,diproses,selesai,ditolak',
            'resolution_note' => 'nullable|string|max:1000',
        ]);

        $report->update([
            'status' => $validated['status'],
            'resolution_note' => $validated['resolution_note'] ?? $report->resolution_note,
            'handled_by' => auth()->id(),
            'handled_at' => now(),
        ]);

        if (in_array($validated['status'], ['selesai', 'ditolak'])) {
            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'report.resolved',
                'description' => "Menyelesaikan laporan #{$report->id} dengan status {$validated['status']}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }

        return back()->with('success', 'Status laporan berhasil diperbarui.');
    }
}
