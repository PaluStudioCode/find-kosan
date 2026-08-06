<?php

namespace App\Helpers;

/**
 * Helper untuk normalisasi nomor WhatsApp ke format 62xxx.
 *
 * Format normalisasi (konsisten dengan ProfileController & RegisteredUserController):
 *  - "+628xxx" -> "628xxx"
 *  - "08xxx"   -> "628xxx"
 *  - "628xxx"  -> "628xxx" (sudah benar)
 *  - "8xxx" (tanpa prefix) -> diasumsikan lokal, diubah ke "628xxx"
 *  - non-digit dibuang
 */
class WhatsappNumber
{
    public static function normalize(string|null $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        // Buang semua karakter non-digit
        $digits = preg_replace('/\D/', '', $raw);

        if ($digits === '' ) {
            return null;
        }

        // Jika diawali "0", ganti dengan "62"
        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        // Jika diawali "+" (sudah dibuang di atas jadi tinggal cek prefix "62")
        // Sudah format 62 -> biarkan
        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        // Jika tidak ada prefix sama sekali (mis. "81234567890"), asumsikan nomor lokal
        return '62' . $digits;
    }

    /**
     * Cek apakah nomor valid format Indonesia.
     */
    public static function isValid(string|null $raw): bool
    {
        $normalized = self::normalize($raw);

        return $normalized !== null
            && strlen($normalized) >= 10
            && strlen($normalized) <= 15;
    }
}
