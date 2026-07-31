<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Facility;
use App\Models\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'menunggu_verifikasi');

        $query = BoardingHouse::with('admin')->latest();

        if ($status !== 'all') {
            if ($status === 'revisi') {
                $query->whereNotNull('pending_revisions');
            } else {
                $query->where('status', $status);
                if ($status === 'dipublikasikan') {
                    // Optional: if you don't want them in dipublikasikan while revising
                    // $query->whereNull('pending_revisions');
                }
            }
        }

        $verifications = $query->paginate(15)->withQueryString();

        return Inertia::render('SuperAdmin/Verifications/Index', [
            'verifications' => $verifications,
            'filters' => ['status' => $status],
        ]);
    }

    public function show(BoardingHouse $kos)
    {
        $kos->load([
            'admin',
            'facilities',
            'rules',
            'rooms.facilities',
            'photos',
            'legalDocuments',
        ]);

        // If there is a shadow revision, overlay it so the Admin sees the proposed changes
        if ($kos->pending_revisions) {
            $kos->fill($kos->pending_revisions);

            if (isset($kos->pending_revisions['facility_ids'])) {
                $kos->setRelation('facilities', Facility::whereIn('id', $kos->pending_revisions['facility_ids'])->get());
            }

            if (isset($kos->pending_revisions['rule_ids'])) {
                $kos->setRelation('rules', Rule::whereIn('id', $kos->pending_revisions['rule_ids'])->get());
            }
        }

        return Inertia::render('SuperAdmin/Verifications/Show', [
            'kos' => $kos,
        ]);
    }

    public function approve(BoardingHouse $kos)
    {
        if ($kos->status === 'menunggu_verifikasi' || $kos->pending_revisions) {
            // Check for shadow revision
            if ($kos->pending_revisions) {
                $revisions = $kos->pending_revisions;

                // Sync many-to-many relationships if present in revisions
                if (isset($revisions['facility_ids'])) {
                    $kos->facilities()->sync($revisions['facility_ids']);
                    unset($revisions['facility_ids']);
                }

                if (isset($revisions['rule_ids'])) {
                    $kos->rules()->sync($revisions['rule_ids']);
                    unset($revisions['rule_ids']);
                }

                // Apply pending revisions
                $kos->update($revisions);
                $kos->pending_revisions = null;
            }

            $kos->status = 'dipublikasikan';
            $kos->verified_at = now();
            $kos->verified_by = auth()->id();
            $kos->verification_note = null;
            $kos->save();

            return redirect()->route('superadmin.verifications.index')->with('success', 'Kos / Revisi berhasil disetujui dan dipublikasikan.');
        }

        return back()->with('error', 'Status kos tidak valid untuk disetujui.');
    }

    public function reject(Request $request, BoardingHouse $kos)
    {
        $request->validate([
            'note' => 'required|string|min:5',
        ]);

        if ($kos->status === 'menunggu_verifikasi' || $kos->pending_revisions) {
            if ($kos->pending_revisions) {
                // If rejecting a shadow revision, clear the pending revisions and revert status back to dipublikasikan
                $kos->pending_revisions = null;
                $kos->status = 'dipublikasikan';
                $kos->verification_note = 'Revisi ditolak: '.$request->note;
            } else {
                $kos->status = 'ditolak';
                $kos->verification_note = $request->note;
            }

            $kos->verified_at = now();
            $kos->verified_by = auth()->id();
            $kos->save();

            return redirect()->route('superadmin.verifications.index')->with('success', 'Kos / Revisi berhasil ditolak.');
        }

        return back()->with('error', 'Status kos tidak valid untuk ditolak.');
    }

    public function downloadLegalDoc(BoardingHouse $kos, $documentId)
    {
        $document = $kos->legalDocuments()->findOrFail($documentId);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::disk('local')->response($document->file_path);
    }
}
