<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment;
use App\Models\BoardingHouse;
use App\Models\LegalDocument;

class MediaController extends Controller
{
    public function show($type, $filename)
    {
        $path = $type . '/' . $filename;
        $user = auth()->user();

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $authorized = false;

        if ($user->role === 'super_admin') {
            $authorized = true;
        } else {
            switch ($type) {
                case 'payments':
                    $payment = Payment::where('proof_file_path', $path)->first();
                    if ($payment && ($payment->tenant_id === $user->id || $payment->owner_id === $user->id)) {
                        $authorized = true;
                    }
                    break;
                case 'qris':
                    // QRIS is attached to BoardingHouse. Owner can see it, and any tenant booking it can see it.
                    $kos = BoardingHouse::where('payment_qris_image_path', $path)->first();
                    if ($kos) {
                        if ($kos->owner_id === $user->id) {
                            $authorized = true;
                        } elseif ($user->role === 'penyewa') {
                            // Can see if they have tenancy for this kos
                            $hasTenancy = $user->tenanciesAsTenant()->whereHas('room', function($q) use ($kos) {
                                $q->where('boarding_house_id', $kos->id);
                            })->exists();
                            if ($hasTenancy) $authorized = true;
                        }
                    }
                    break;
                case 'legal_documents':
                    $doc = LegalDocument::where('file_path', $path)->first();
                    if ($doc && $doc->boardingHouse->owner_id === $user->id) {
                        $authorized = true;
                    }
                    break;
            }
        }

        if (!$authorized) {
            abort(403, 'Unauthorized access to this media.');
        }

        return response()->file(Storage::disk('local')->path($path));
    }
}
