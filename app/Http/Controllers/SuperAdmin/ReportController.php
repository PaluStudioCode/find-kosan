<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with(['reporter', 'boardingHouse.admin']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('boardingHouse.admin', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%");
                })->orWhereHas('boardingHouse', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%");
                })->orWhereHas('reporter', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('category') && $request->category != 'all') {
            $query->where('category', $request->category);
        }

        $reports = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('SuperAdmin/Reports/Index', [
            'reports' => $reports,
            'filters' => request()->all(['search', 'status', 'category']),
        ]);
    }

    public function show(Report $report)
    {
        $report->load(['reporter', 'boardingHouse.admin', 'handler']);

        return Inertia::render('SuperAdmin/Reports/Show', [
            'report' => $report,
        ]);
    }

    public function update(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => 'required|in:menunggu,selesai',
            'resolution_note' => 'required|string|min:5|max:1000',
            'sanction' => 'nullable|in:none,suspend_kos,ban_kos,ban_admin',
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
                // Change status to nonaktif so it hides from public search
                $kos->update(['status' => 'nonaktif']);
            } elseif ($sanction === 'ban_kos') {
                // Soft delete the boarding house
                $kos->delete();
            } elseif ($sanction === 'ban_admin') {
                // Change owner status to banned/inactive and soft delete their kos
                if ($kos->admin) {
                    $kos->admin->update(['status' => 'banned']); // Or inactive depending on user statuses
                }
                $kos->delete();
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'report.sanction_applied',
                'description' => "Menerapkan sanksi '{$sanction}' berdasarkan laporan #{$report->id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Auto-resolve laporan lain yang masih menunggu untuk kos ini
            if ($kos) {
                Report::where('boarding_house_id', $kos->id)
                    ->where('id', '!=', $report->id)
                    ->where('status', 'menunggu')
                    ->update([
                        'status' => 'selesai',
                        'resolution_note' => "Laporan ditutup otomatis karena properti telah dijatuhi sanksi '{$sanction}' berdasarkan Laporan #{$report->id}.",
                        'handled_by' => auth()->id(),
                        'handled_at' => now(),
                    ]);
            }
        }

        if ($validated['status'] === 'selesai') {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'report.resolved',
                'description' => "Menyelesaikan laporan #{$report->id} dengan status {$validated['status']}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return back()->with('success', 'Status laporan dan sanksi berhasil diperbarui.');
    }

    public function destroy(Report $report)
    {
        // Activity log for deletion
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'report.deleted',
            'description' => "Menghapus laporan #{$report->id} (dianggap tidak valid/ngawur)",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $report->forceDelete(); // Menghapus secara permanen dari sistem

        return redirect()->route('superadmin.reports.index')->with('success', 'Laporan tidak valid berhasil dihapus dari sistem.');
    }
}
