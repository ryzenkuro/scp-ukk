<?php

namespace Database\Seeders;

use App\Services\MasterDataService;
use Illuminate\Database\Seeder;

class OfficialMasterGuruSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(MasterDataService $masterDataService): void
    {
        $results = $masterDataService->syncToDatabase(['only' => 'guru']);

        foreach ($results as $entity => $msg) {
            $this->command?->info(' ✔ ['.strtoupper($entity)."] {$msg}");
        }
    }
}
