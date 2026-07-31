<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Rule;
use Illuminate\Http\Request;

class RuleController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:rules,name',
            'is_positive' => 'required|boolean',
        ]);

        Rule::create($validated);

        return back()->with('success', 'Peraturan berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rule $rule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:rules,name,'.$rule->id,
            'is_positive' => 'required|boolean',
        ]);

        $rule->update($validated);

        return back()->with('success', 'Peraturan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rule $rule)
    {
        $rule->delete();

        return back()->with('success', 'Peraturan berhasil dihapus.');
    }
}
