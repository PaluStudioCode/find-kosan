<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\BoardingHouseLegalDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LegalDocumentController extends Controller
{
    public function store(Request $request, BoardingHouse $kos)
    {
        if ($kos->owner_id !== auth()->id()) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Data tidak dapat diubah karena sedang dalam proses peninjauan.');
        }

        $request->validate([
            'document_type' => 'required|string|max:50',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120' // 5MB limit
        ]);

        $path = $request->file('file')->store('legal_documents', 'local');

        $kos->legalDocuments()->create([
            'document_type' => $request->document_type,
            'document_name' => $request->file('file')->getClientOriginalName(),
            'file_path' => $path,
            'status' => 'menunggu_review',
        ]);

        return back()->with('success', 'Dokumen legalitas berhasil diunggah.');
    }

    public function destroy(BoardingHouse $kos, BoardingHouseLegalDocument $legalDocument)
    {
        if ($kos->owner_id !== auth()->id() || $legalDocument->boarding_house_id !== $kos->id) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Data tidak dapat diubah karena sedang dalam proses peninjauan.');
        }
        
        if (Storage::disk('local')->exists($legalDocument->file_path)) {
            Storage::disk('local')->delete($legalDocument->file_path);
        }
        $legalDocument->forceDelete();

        return back()->with('success', 'Dokumen legalitas berhasil dihapus.');
    }
}
