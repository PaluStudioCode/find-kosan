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
        $report->load(['reporter', 'boardingHouse.owner', 'handler']);
        return Inertia::render('Admin/Reports/Show', [
            'report' => $report
        ]);
    }

    public function update(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => 'required|in:menunggu,selesai',
            'resolution_note' => 'nullable|string|max:1000',
            'sanction' => 'nullable|in:none,suspend_kos,ban_kos,ban_owner',
        ]);

        $report->update([
            'status' => $validated['status'],
            'resolution_note' => $validated['resolution_note'] ?? $report->resolution_note,
            'handled_by' => auth()->id(),
            'handled_at' => now(),
        ]);
        
        // Execute Sanction Logic
        $sanction = $validated['sanction'] ?? 'none';
        if ($sanction !== 'none') {
            $kos = $report->boardingHouse;
            
            if ($sanction === 'suspend_kos') {
                // Change status to suspended so it hides from public search
                $kos->update(['status' => 'suspended']);
            } elseif ($sanction === 'ban_kos') {
                // Soft delete the boarding house
                $kos->delete();
            } elseif ($sanction === 'ban_owner') {
                // Change owner status to banned/inactive and soft delete their kos
                if ($kos->owner) {
                    $kos->owner->update(['status' => 'banned']); // Or inactive depending on user statuses
                }
                $kos->delete();
            }
            
            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'report.sanction_applied',
                'description' => "Menerapkan sanksi '{$sanction}' berdasarkan laporan #{$report->id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }

        if ($validated['status'] === 'selesai') {
            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'report.resolved',
                'description' => "Menyelesaikan laporan #{$report->id} dengan status {$validated['status']}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }

        return back()->with('success', 'Status laporan dan sanksi berhasil diperbarui.');
    }

    public function destroy(Report $report)
    {
        // Activity log for deletion
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'report.deleted',
            'description' => "Menghapus laporan #{$report->id} (dianggap tidak valid/ngawur)",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        $report->forceDelete(); // Menghapus secara permanen dari sistem

        return redirect()->route('admin.reports.index')->with('success', 'Laporan tidak valid berhasil dihapus dari sistem.');
    }
}
