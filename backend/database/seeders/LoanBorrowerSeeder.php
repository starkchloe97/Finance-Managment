<?php

namespace Database\Seeders;

use Database\Factories\LoanBorrowerFactory;
use Illuminate\Database\Seeder;

class LoanBorrowerSeeder extends Seeder
{
    public function run(): void
    {
        LoanBorrowerFactory::new()->count(4)->create();
    }
}
