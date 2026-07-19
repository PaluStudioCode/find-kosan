<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $query = BoardingHouse::with('owner')->latest();

        // Default to pending verifications if no status selected
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else if (!$request->has('status')) {
            $query->where('status', 'menunggu_verifikasi');
        }

        $verifications = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Verifications/Index', [
            'verifications' => $verifications,
            'filters' => request()->all(['status'])
        ]);
    }

    public function show(BoardingHouse $kos)
    {
        $kos->load(['owner', 'legalDocuments', 'photos', 'rooms', 'facilities']);

        return Inertia::render('Admin/Verifications/Show', [
            'kos' => $kos
        ]);
    }

    public function approve(BoardingHouse $kos)
    {
        if ($kos->status === 'menunggu_verifikasi') {
            // Check for shadow revision
            if ($kos->pending_revisions) {
                // Apply pending revisions
                $kos->update($kos->pending_revisions);
                $kos->pending_revisions = null;
            }
            
            $kos->status = 'dipublikasikan';
            $kos->verified_at = now();
            $kos->verified_by = auth()->id();
            $kos->verification_note = null;
            $kos->save();

            return redirect()->route('admin.verifications.index')->with('success', 'Kos berhasil disetujui dan dipublikasikan.');
        }

        return back()->with('error', 'Status kos tidak valid untuk disetujui.');
    }

    public function reject(Request $request, BoardingHouse $kos)
    {
        $request->validate([
            'note' => 'required|string|min:5'
        ]);

        if ($kos->status === 'menunggu_verifikasi') {
            if ($kos->pending_revisions) {
                // If rejecting a shadow revision, we just clear the pending revisions and revert status back to dipublikasikan?
                // Or maybe set status to dipublikasikan but add a verification note?
                // PRD: "Data lama yang valid tetap akan tampil di publik sampai revisi disetujui". If rejected, it stays dipublikasikan but revision is cleared.
                $kos->pending_revisions = null;
                $kos->status = 'dipublikasikan';
                $kos->verification_note = 'Revisi ditolak: ' . $request->note;
            } else {
                $kos->status = 'ditolak';
                $kos->verification_note = $request->note;
            }
            
            $kos->verified_at = now();
            $kos->verified_by = auth()->id();
            $kos->save();

            return redirect()->route('admin.verifications.index')->with('success', 'Kos / Revisi berhasil ditolak.');
        }

        return back()->with('error', 'Status kos tidak valid untuk ditolak.');
    }

    public function downloadLegalDoc(BoardingHouse $kos, $documentId)
    {
        $document = $kos->legalDocuments()->findOrFail($documentId);
        
        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::disk('local')->response($document->file_path);
    }
}
