<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;

class RegionController extends Controller
{
    public function provinces()
    {
        return response()->json(Province::orderBy('name')->get());
    }

    public function cities(Request $request)
    {
        $query = City::orderBy('name');
        if ($request->has('province_code')) {
            $query->where('province_code', $request->province_code);
        }
        return response()->json($query->get());
    }

    public function districts(Request $request)
    {
        $query = District::orderBy('name');
        if ($request->has('city_code')) {
            $query->where('city_code', $request->city_code);
        }
        return response()->json($query->get());
    }

    public function villages(Request $request)
    {
        $query = Village::orderBy('name');
        if ($request->has('district_code')) {
            $query->where('district_code', $request->district_code);
        }
        return response()->json($query->get());
    }
    
    public function reverseGeocodeMatch(Request $request)
    {
        $displayName = $request->display_name;
        
        $matchedCity = null;
        $matchedDistrict = null;
        $matchedVillage = null;
        
        if (!$displayName) {
            return response()->json(['city' => null, 'district' => null, 'village' => null]);
        }

        // Cari City
        $cities = City::all();
        foreach ($cities as $c) {
            $nameToSearch = trim(str_replace(['KOTA ADMINISTRASI ', 'KABUPATEN ADMINISTRASI ', 'KOTA ', 'KABUPATEN '], '', $c->name));
            if (stripos($displayName, $nameToSearch) !== false) {
                $matchedCity = $c;
                break;
            }
        }
        
        // Cari District
        if ($matchedCity) {
            $districts = District::where('city_code', $matchedCity->code)->get();
            foreach ($districts as $d) {
                $nameToSearch = trim(str_replace(['KECAMATAN '], '', $d->name));
                if (stripos($displayName, $nameToSearch) !== false) {
                    $matchedDistrict = $d;
                    break;
                }
            }
        }
        
        // Cari Village
        if ($matchedDistrict) {
            $villages = Village::where('district_code', $matchedDistrict->code)->get();
            foreach ($villages as $v) {
                $nameToSearch = trim(str_replace(['KELURAHAN ', 'DESA '], '', $v->name));
                if (stripos($displayName, $nameToSearch) !== false) {
                    $matchedVillage = $v;
                    break;
                }
            }
        }
        
        return response()->json([
            'city' => $matchedCity,
            'district' => $matchedDistrict,
            'village' => $matchedVillage
        ]);
    }
}
