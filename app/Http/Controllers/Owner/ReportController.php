<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $ownerId = auth()->id();

        // Get reports for boarding houses owned by this owner
        $reports = Report::whereHas('boardingHouse', function ($query) use ($ownerId) {
                $query->where('owner_id', $ownerId);
            })
            ->with(['reporter:id,name,email', 'boardingHouse:id,name'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Owner/Reports/Index', [
            'reports' => $reports
        ]);
    }
}
