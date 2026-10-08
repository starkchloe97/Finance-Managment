<?php

use App\Services\VehicleContractService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('payables:generate-vehicle-rentals', function (VehicleContractService $contracts) {
    $count = $contracts->generateCurrentMonthPayables();
    $this->info("Created {$count} vehicle rental payable(s).");
})->purpose('Generate current-month payables for active vehicle contracts');

Schedule::command('payables:generate-vehicle-rentals')->daily();
