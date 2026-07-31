<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Rule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterDataController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'facilities');

        $facilitiesQuery = Facility::query();
        if ($request->has('search_facility')) {
            $facilitiesQuery->where('name', 'like', '%'.$request->search_facility.'%');
        }
        if ($request->has('type') && $request->type !== 'all') {
            $facilitiesQuery->where('type', $request->type);
        }
        $facilities = $facilitiesQuery->orderBy('type')
            ->orderBy('name')
            ->paginate(5, ['*'], 'facility_page')
            ->withQueryString();

        $rulesQuery = Rule::query();
        if ($request->has('search_rule')) {
            $rulesQuery->where('name', 'like', '%'.$request->search_rule.'%');
        }
        $rules = $rulesQuery->orderBy('name')
            ->paginate(5, ['*'], 'rule_page')
            ->withQueryString();

        return Inertia::render('SuperAdmin/MasterData/Index', [
            'facilities' => $facilities,
            'rules' => $rules,
            'activeTab' => $activeTab,
            'filters' => $request->only(['search_facility', 'search_rule', 'type']),
        ]);
    }
}
