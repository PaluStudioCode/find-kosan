<?php

namespace App\Console\Commands;

use App\Models\BoardingHouse;
use App\Models\BoardingHousePhoto;
use App\Models\LegalDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PruneSoftDeletedData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prune:soft-deleted {--days=30 : The number of days to keep soft deleted records before permanent deletion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete soft-deleted boarding houses and their associated physical files (images/documents) older than the specified days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $this->info("Mencari data yang di-soft delete lebih dari {$days} hari...");

        // Threshold date
        $threshold = now()->subDays($days);

        // Get Boarding Houses that were soft deleted older than threshold
        $deletedKos = BoardingHouse::onlyTrashed()->where('deleted_at', '<=', $threshold)->get();

        if ($deletedKos->isEmpty()) {
            $this->info("Tidak ada data sampah (soft delete) yang melewati {$days} hari.");

            return;
        }

        $count = 0;
        foreach ($deletedKos as $kos) {

            // 2. Delete Boarding House Photos
            $photos = BoardingHousePhoto::withTrashed()->where('boarding_house_id', $kos->id)->get();
            foreach ($photos as $photo) {
                $photoPath = str_replace('/storage/', '', $photo->file_path);
                if (Storage::disk('public')->exists($photoPath)) {
                    Storage::disk('public')->delete($photoPath);
                }
                $photo->forceDelete();
            }

            // 3. Delete Legal Documents
            $documents = LegalDocument::withTrashed()->where('boarding_house_id', $kos->id)->get();
            foreach ($documents as $document) {
                if (Storage::disk('local')->exists($document->file_path)) {
                    Storage::disk('local')->delete($document->file_path);
                }
                $document->forceDelete();
            }

            // The rooms and tenancies associated will be cascadingly force-deleted if DB has cascade,
            // but since Laravel doesn't cascade forceDelete for softdeletes automatically,
            // we should manually force delete them if needed, or rely on Eloquent.
            $kos->rooms()->withTrashed()->forceDelete();
            $kos->tenancies()->withTrashed()->forceDelete();

            // Finally, force delete the boarding house
            $kos->forceDelete();

            $count++;
        }

        $this->info("Berhasil membersihkan {$count} data properti kos beserta seluruh file gambarnya secara permanen.");
        Log::info("Auto-Prune: Berhasil membersihkan {$count} data kos yang melewati batas {$days} hari.");
    }
}
