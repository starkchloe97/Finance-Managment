<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CustomerSeeder::class,
            AssetSeeder::class,
            InvestorSeeder::class,
            LoanBorrowerSeeder::class,
            EstimateAndJobSeeder::class,
            InvestmentSeeder::class,
            InvestmentAllocationSeeder::class,
            CompanyCapitalSeeder::class,
            LoanSeeder::class,
            VehicleContractSeeder::class,
        ]);
    }
}
