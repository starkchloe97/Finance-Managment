<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        Asset::factory(10)->create([
            'created_by' => $user?->id,
        ]);
    }
}
