<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::where('reporter_id', auth()->id())
            ->with('boardingHouse')
            ->latest()
            ->get();

        return Inertia::render('Reports/Index', [
            'reports' => $reports
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'boarding_house_id' => 'nullable|exists:boarding_houses,id',
            'category' => 'required|in:data_kos_tidak_valid,kontak_tidak_valid,foto_tidak_sesuai,lainnya',
            'description' => 'required|string|max:1000',
        ]);

        Report::create([
            'reporter_id' => auth()->id(),
            'boarding_house_id' => $validated['boarding_house_id'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'status' => 'menunggu',
        ]);

        return back()->with('success', 'Laporan berhasil dikirim.');
    }
}
